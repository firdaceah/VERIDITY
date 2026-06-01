<?php

namespace App\Http\Controllers;

use App\Services\VeridityProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // 1. Menampilkan Halaman Dashboard / Beranda Utama Reseller
    public function landing()
    {
        $featured = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->orderBy('products.discount_percentage', 'desc')
            ->limit(8)
            ->get();

        return view('distri.landing', compact('featured'));
    }

    // 2. Menampilkan Halaman Katalog Reseller dari Oracle
    public function catalog()
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name', 'categories.slug as category_slug');

        if (request('q')) {
            $search = '%'.request('q').'%';
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', $search)
                    ->orWhere('products.brand', 'like', $search);
            });
        }

        if (request('category')) {
            $query->where('categories.slug', request('category'));
        }

        match (request('sort')) {
            'price_low' => $query->orderBy('products.price'),
            'price_high' => $query->orderByDesc('products.price'),
            'rating' => $query->orderByDesc('products.rating'),
            default => $query->orderByDesc('products.id'),
        };

        $products = $query->get();
        $categories = DB::table('categories')->orderBy('name')->get();
        return view('distri.catalog', compact('products', 'categories'));
    }

    public function productDetail($id)
    {
        $product = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.id', $id)
            ->select('products.*', 'categories.name as category_name')
            ->first();

        if (! $product) {
            abort(404);
        }

        $related = DB::table('products')
            ->where('id', '!=', $id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->limit(4)
            ->get();

        return view('distri.product-detail', compact('product', 'related'));
    }

    // 3. Menampilkan Form Checkout Transaksi
    public function checkout($id)
    {
        $this->ensureDefaultVouchers();
        $paymentMethods = config('payment_methods');
        $items = $this->cartItems();

        if ($items->isEmpty() && $id !== 'cart') {
            $product = DB::table('products')->where('id', $id)->first();
            if (!$product) abort(404);

            $items = collect([(object) [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => max(1, (int) $product->min_qty),
                'image' => $product->image,
                'image_url' => $product->image_url ?? null,
                'stock' => $product->stock ?? 50,
                'category_name' => null,
            ]]);
        }

        if ($items->isEmpty()) {
            return redirect()->route('distri.cart')->with('error', 'Keranjang masih kosong.');
        }

        return view('distri.checkout', [
            'items' => $items,
            'paymentMethods' => $paymentMethods,
            'totalAmount' => $this->cartTotal($items),
            'addresses' => DB::table('shipping_addresses')->where('user_id', Auth::id())->orderByDesc('is_default')->get(),
            'vouchers' => DB::table('vouchers')->where('is_active', true)->orderBy('minimum_order')->get(),
        ]);
    }

    // 4. STORE ORDER: Memproses pesanan + Upload Nota Langsung ke Folder Public Terluar
    public function storeOrder(Request $request, VeridityProofService $veridityProofService)
    {
        $paymentMethods = config('payment_methods');
        $methodKey = (string) $request->input('payment_method');
        $channelKey = (string) $request->input('payment_channel');
        $selectedMethod = $paymentMethods[$methodKey] ?? null;
        $selectedChannel = $selectedMethod['channels'][$channelKey] ?? null;
        $requiresProof = (bool) ($selectedMethod['requires_proof'] ?? true);

        $request->validate([
            'product_id' => 'nullable',
            'quantity' => 'nullable|numeric|min:1',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_channel' => 'required|string',
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'voucher_code' => 'nullable|string|max:40',
            'proof_of_transfer' => ($requiresProof ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png|max:10120',
        ]);

        if (! $selectedMethod || ! $selectedChannel) {
            return back()->withErrors(['payment_method' => 'Metode pembayaran tidak valid.'])->withInput();
        }

        $items = $this->cartItems();
        if ($items->isEmpty()) {
            $product = DB::table('products')->where('id', $request->product_id)->first();
            if (! $product) {
                return redirect()->route('distri.cart')->with('error', 'Produk tidak ditemukan.');
            }
            $items = collect([(object) [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => max(1, (int) $request->quantity),
            ]]);
        }

        $grossAmount = $this->cartTotal($items);
        $voucher = $this->findVoucher($request->voucher_code, $grossAmount);
        $discountAmount = $voucher ? $this->voucherDiscount($voucher, $grossAmount) : 0;
        $totalAmount = max(0, $grossAmount - $discountAmount);
        $address = DB::table('shipping_addresses')
            ->where('id', $request->shipping_address_id)
            ->where('user_id', Auth::id())
            ->first();

        // --- SOLUSI AMAN GAMBAR: Dipindahkan langsung ke public/proofs ---
        $fileName = null;
        if ($request->hasFile('proof_of_transfer')) {
            $file = $request->file('proof_of_transfer');
            $fileName = 'PROOF_' . time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

            // Laravel otomatis membuat folder 'proofs' di dalam 'public/' jika belum ada
            $file->move(public_path('proofs'), $fileName);
        }

        // Format Invoice sesuai bawaan kode awalmu
        $orderId = 'TRX-' . rand(1000, 9999);
        $veridityData = [
            'veridity_status' => $requiresProof ? 'checking' : 'not_required',
            'payment_status' => $requiresProof ? 'checking' : 'pending_cod',
            'veridity_message' => $requiresProof
                ? 'Bukti pembayaran sedang dikirim ke VERIDITY.'
                : 'Metode pembayaran tidak membutuhkan unggah bukti.',
            'veridity_validation_details' => null,
            'veridity_checked_at' => $requiresProof ? null : now(),
        ];

        if ($requiresProof && $fileName) {
            $veridityData = array_merge($veridityData, $veridityProofService->analyze(
                $fileName,
                $orderId,
                [
                    'method' => $methodKey,
                    'channel' => $channelKey,
                    'amount' => $totalAmount,
                    'recipient_name' => $selectedChannel['recipient_name'] ?? '',
                    'recipient_account' => $selectedChannel['recipient_account'] ?? '',
                    'instruction' => $selectedChannel['instruction'],
                ]
            ));
        }

        // Simpan Data rill ke tabel ORDERS skema Oracle
        DB::table('orders')->insert([
            'order_id_string' => $orderId,
            'user_id' => Auth::id(),
            'product_id' => $items->first()->product_id,
            'quantity' => $items->sum('quantity'),
            'total_amount' => $totalAmount,
            'proof_of_transfer' => $fileName,
            'payment_method' => $methodKey,
            'payment_channel' => $channelKey,
            'payment_status' => $veridityData['payment_status'],
            'payment_instruction' => $selectedChannel['instruction'],
            'shipping_address' => $address ? json_encode($address) : null,
            'voucher_code' => $voucher->code ?? null,
            'discount_amount' => $discountAmount,
            'veridity_status' => $veridityData['veridity_status'],
            'veridity_audit_id' => $veridityData['veridity_audit_id'] ?? null,
            'veridity_score' => $veridityData['veridity_score'] ?? null,
            'veridity_message' => $veridityData['veridity_message'] ?? null,
            'veridity_validation_details' => $veridityData['veridity_validation_details'] ?? null,
            'veridity_checked_at' => $veridityData['veridity_checked_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $order = DB::table('orders')->where('order_id_string', $orderId)->where('user_id', Auth::id())->first();

        foreach ($items as $item) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->price * $item->quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('cart_items')->where('user_id', Auth::id())->delete();

        return redirect()->route('distri.order.show', $order->id)->with('success', 'Pesanan berhasil dikirim! Status validasi pembayaran sudah diperbarui.');
    }

    // 5. Menampilkan Riwayat Pesanan Reseller (Join Table)
    public function orderHistory()
    {
        $orders = DB::table('orders')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->where('orders.user_id', Auth::id())
            // Pastikan products.unit dimasukkan ke dalam select di bawah ini
            ->select('orders.*', 'products.name as product_name', 'products.image as product_image', 'products.unit')
            ->orderBy('orders.created_at', 'desc')
            ->get();

        return view('distri.orders', compact('orders'));
    }

    public function profile()
    {
        $summary = [
            'orders' => DB::table('orders')
                ->where('user_id', Auth::id())
                ->where('payment_status', '!=', 'rejected')
                ->count(),
            'rejected' => DB::table('orders')->where('user_id', Auth::id())->where('payment_status', 'rejected')->count(),
            'paid' => DB::table('orders')->where('user_id', Auth::id())->where('payment_status', 'paid')->count(),
            'cart' => DB::table('cart_items')->where('user_id', Auth::id())->sum('quantity'),
        ];

        $orders = DB::table('orders')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $addresses = DB::table('shipping_addresses')->where('user_id', Auth::id())->orderByDesc('is_default')->get();

        return view('distri.profile', ['user' => Auth::user(), 'summary' => $summary, 'orders' => $orders, 'addresses' => $addresses]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $emailExists = DB::table('users')
            ->where('email', $validated['email'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailExists) {
            return back()->withErrors(['email' => 'Email sudah digunakan toko lain.']);
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'updated_at' => now(),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        DB::table('users')->where('id', $user->id)->update($payload);

        return back()->with('success', 'Profile toko berhasil diperbarui.');
    }

    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:80',
            'recipient_name' => 'required|string|max:160',
            'phone' => 'nullable|string|max:40',
            'address_line' => 'required|string',
            'city' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            DB::table('shipping_addresses')->where('user_id', Auth::id())->update(['is_default' => false]);
        }

        DB::table('shipping_addresses')->insert(array_merge($validated, [
            'user_id' => Auth::id(),
            'is_default' => $request->boolean('is_default'),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Alamat pengiriman berhasil ditambahkan.');
    }

    public function vouchers()
    {
        $this->ensureDefaultVouchers();

        return view('distri.vouchers', [
            'vouchers' => DB::table('vouchers')->where('is_active', true)->orderBy('minimum_order')->get(),
        ]);
    }

    public function showOrder($id)
    {
        $order = DB::table('orders')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->where('orders.id', $id)
            ->where('orders.user_id', Auth::id())
            ->select('orders.*', 'products.name as product_name', 'products.image as product_image', 'products.unit')
            ->first();

        if (! $order) {
            abort(404);
        }

        return view('distri.order-detail', [
            'order' => $order,
            'items' => DB::table('order_items')->where('order_id', $order->id)->get(),
            'validation' => $this->decodeValidationDetails($order->veridity_validation_details ?? null),
        ]);
    }

    private function cartItems()
    {
        return DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('cart_items.user_id', Auth::id())
            ->select('cart_items.*', 'products.id as product_id', 'products.name', 'products.price', 'products.image', 'products.image_url', 'products.stock', 'categories.name as category_name')
            ->orderBy('cart_items.created_at', 'desc')
            ->get();
    }

    private function cartTotal($items): int
    {
        return (int) $items->sum(fn ($item) => $item->price * $item->quantity);
    }

    private function findVoucher(?string $code, int $total): ?object
    {
        if (! $code) {
            return null;
        }

        return DB::table('vouchers')
            ->where('code', strtoupper($code))
            ->where('is_active', true)
            ->where('minimum_order', '<=', $total)
            ->first();
    }

    private function voucherDiscount(object $voucher, int $total): int
    {
        if ($voucher->type === 'percent') {
            return (int) min($total, round($total * ((float) $voucher->value / 100)));
        }

        return (int) min($total, $voucher->value);
    }

    private function ensureDefaultVouchers(): void
    {
        $defaults = [
            ['code' => 'HEMAT10', 'name' => 'Diskon 10% Belanja Minimarket', 'type' => 'percent', 'value' => 10, 'minimum_order' => 50000],
            ['code' => 'ONGKIR15', 'name' => 'Potongan Rp15.000', 'type' => 'fixed', 'value' => 15000, 'minimum_order' => 100000],
        ];

        foreach ($defaults as $voucher) {
            DB::table('vouchers')->updateOrInsert(
                ['code' => $voucher['code']],
                array_merge($voucher, ['is_active' => true, 'updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    private function decodeValidationDetails(?string $payload): array
    {
        if (! $payload) {
            return ['status' => 'empty', 'summary' => 'Belum ada detail validasi.', 'checks' => []];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : ['status' => 'error', 'summary' => 'Detail validasi tidak dapat dibaca.', 'checks' => []];
    }

    // 6. CRUD DELETE: Membatalkan/Menghapus Transaksi Grosir sekaligus membersihkan file fisiknya
    public function destroyOrder($id)
    {
        $order = DB::table('orders')->where('id', $id)->where('user_id', Auth::id())->first();

        if ($order) {
            // Bersihkan file fisiknya dari folder public/proofs biar gak menumpuk jadi sampah di Windows
            if ($order->proof_of_transfer) {
                $filePath = public_path('proofs/' . $order->proof_of_transfer);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Hapus record dari Oracle
            DB::table('orders')->where('id', $id)->where('user_id', Auth::id())->delete();
            return redirect()->route('distri.orders')->with('success', 'Pesanan berhasil dibatalkan dan dihapus dari database.');
        }

        return back()->with('error', 'Pesanan gagal dibatalkan.');
    }
}
