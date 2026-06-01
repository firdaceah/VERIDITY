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
            $search = '%'.strtolower(request('q')).'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(products.name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(products.brand) LIKE ?', [$search]);
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
        if ($id !== 'cart') {
            $product = DB::table('products')->where('id', $id)->first();
            if (!$product) abort(404);

            $items = collect([(object) [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $this->discountedPrice($product),
                'original_price' => $product->price,
                'discount_percentage' => $product->discount_percentage ?? 0,
                'quantity' => max(1, (int) $product->min_qty),
                'image' => $product->image,
                'image_url' => $product->image_url ?? null,
                'stock' => $product->stock ?? 50,
                'category_name' => null,
            ]]);
            $directCheckout = true;
        } else {
            $selectedCartIds = collect(session('checkout_cart_item_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();
            $items = $this->cartItems($selectedCartIds);
            $directCheckout = false;
        }

        if ($items->isEmpty()) {
            return redirect()->route('distri.cart')->with('error', 'Keranjang masih kosong.');
        }

        return view('distri.checkout', [
            'items' => $items,
            'paymentMethods' => $paymentMethods,
            'totalAmount' => $this->cartTotal($items),
            'shippingFee' => 15000,
            'directCheckout' => $directCheckout,
            'selectedCartIds' => $selectedCartIds ?? [],
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
            'direct_checkout' => 'nullable|boolean',
            'proof_of_transfer' => ($requiresProof ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png|max:10120',
        ]);

        if (! $selectedMethod || ! $selectedChannel) {
            return back()->withErrors(['payment_method' => 'Metode pembayaran tidak valid.'])->withInput();
        }

        $selectedCartIds = collect($request->input('cart_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $items = $request->boolean('direct_checkout') ? collect() : $this->cartItems($selectedCartIds);
        if ($items->isEmpty()) {
            $product = DB::table('products')->where('id', $request->product_id)->first();
            if (! $product) {
                return redirect()->route('distri.cart')->with('error', 'Produk tidak ditemukan.');
            }
            $items = collect([(object) [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $this->discountedPrice($product),
                'original_price' => $product->price,
                'discount_percentage' => $product->discount_percentage ?? 0,
                'quantity' => max(1, (int) $request->quantity),
            ]]);
        }

        $grossAmount = $this->cartTotal($items);
        $voucher = $this->findVoucher($request->voucher_code, $grossAmount);
        $discountAmount = $voucher ? $this->voucherDiscount($voucher, $grossAmount) : 0;
        $shippingFee = 15000;
        $totalAmount = max(0, $grossAmount - $discountAmount) + $shippingFee;
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
            'payment_status' => $requiresProof ? 'checking' : 'cod_on_delivery',
            'order_status' => $requiresProof ? 'checking' : 'packing',
            'veridity_message' => $requiresProof
                ? 'Bukti pembayaran sedang dikirim ke VERIDITY.'
                : 'COD tidak membutuhkan validasi nota. Pesanan langsung masuk tahap dikemas dan dibayar saat diterima.',
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

        $veridityData['order_status'] = match ($veridityData['payment_status'] ?? null) {
            'paid' => 'packing',
            'rejected' => 'rejected',
            'cod_on_delivery' => 'packing',
            default => 'checking',
        };

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
            'order_status' => $veridityData['order_status'] ?? (($veridityData['payment_status'] ?? '') === 'paid' ? 'packing' : 'checking'),
            'payment_instruction' => $selectedChannel['instruction'],
            'shipping_address' => $address ? json_encode($address) : null,
            'voucher_code' => $voucher->code ?? null,
            'discount_amount' => $discountAmount,
            'shipping_fee' => $shippingFee,
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

        if (! $request->boolean('direct_checkout')) {
            $cartDelete = DB::table('cart_items')->where('user_id', Auth::id());
            if (! empty($selectedCartIds)) {
                $cartDelete->whereIn('id', $selectedCartIds);
            }
            $cartDelete->delete();
            session()->forget('checkout_cart_item_ids');
        }

        return redirect()->route('distri.order.show', $order->id)->with('success', 'Pesanan berhasil dikirim! Status validasi pembayaran sudah diperbarui.');
    }

    // 5. Menampilkan Riwayat Pesanan Reseller (Join Table)
    public function orderHistory()
    {
        $status = request('status', 'packing');
        $query = DB::table('orders')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->where('orders.user_id', Auth::id())
            ->select('orders.*', 'products.name as product_name', 'products.image as product_image', 'products.unit');

        match ($status) {
            'shipped' => $query->where('orders.order_status', 'shipped'),
            'received' => $query->where('orders.order_status', 'received'),
            'canceled' => $query->where(function ($q) {
                $q->where('orders.order_status', 'rejected')
                    ->orWhere('orders.payment_status', 'rejected');
            }),
            default => $query->whereIn('orders.order_status', ['checking', 'packing'])
                ->where('orders.payment_status', '!=', 'rejected')
                ->where('orders.veridity_status', '!=', 'rejected'),
        };

        if (request('q')) {
            $search = '%' . strtolower(request('q')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(orders.order_id_string) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(products.name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(orders.payment_method) LIKE ?', [$search]);
            });
        }

        $orders = $query->orderBy('orders.created_at', 'desc')->get();
        $counts = [
            'packing' => DB::table('orders')->where('user_id', Auth::id())
                ->whereIn('order_status', ['checking', 'packing'])
                ->where('payment_status', '!=', 'rejected')
                ->where('veridity_status', '!=', 'rejected')
                ->count(),
            'shipped' => DB::table('orders')->where('user_id', Auth::id())->where('order_status', 'shipped')->count(),
            'received' => DB::table('orders')->where('user_id', Auth::id())->where('order_status', 'received')->count(),
            'canceled' => DB::table('orders')->where('user_id', Auth::id())->where(function ($q) {
                $q->where('order_status', 'rejected')->orWhere('payment_status', 'rejected');
            })->count(),
        ];

        return view('distri.orders', compact('orders', 'status', 'counts'));
    }

    public function profile()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return view('distri.profile', [
                'user' => $user,
                'adminSummary' => [
                    'products' => DB::table('products')->count(),
                    'stores' => DB::table('users')->where('role', 'reseller')->count(),
                    'orders_active' => DB::table('orders')->whereIn('order_status', ['checking', 'packing', 'shipped'])->count(),
                    'need_validation' => DB::table('orders')->whereIn('veridity_status', ['checking', 'review_required', 'error'])->count(),
                ],
                'summary' => null,
                'orders' => collect(),
                'addresses' => collect(),
            ]);
        }

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

        return redirect()->route('distri.profile')->with('success', 'Profile toko berhasil diperbarui.');
    }

    public function editProfile()
    {
        return view('distri.profile-edit', ['user' => Auth::user()]);
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

    private function cartItems(array $onlyCartItemIds = [])
    {
        $query = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('cart_items.user_id', Auth::id())
            ->select('cart_items.*', 'products.id as product_id', 'products.name', 'products.price as original_price', 'products.discount_percentage', 'products.image', 'products.image_url', 'products.stock', 'categories.name as category_name')
            ->orderBy('cart_items.created_at', 'desc');

        if (! empty($onlyCartItemIds)) {
            $query->whereIn('cart_items.id', $onlyCartItemIds);
        }

        return $query->get()->map(function ($item) {
            $item->price = $this->discountedPrice($item);
            return $item;
        });
    }

    private function cartTotal($items): int
    {
        return (int) $items->sum(fn ($item) => $item->price * $item->quantity);
    }

    private function discountedPrice(object $item): int
    {
        $price = (float) ($item->original_price ?? $item->price ?? 0);
        $discount = max(0, min(100, (float) ($item->discount_percentage ?? 0)));

        return (int) round($price - ($price * $discount / 100));
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
