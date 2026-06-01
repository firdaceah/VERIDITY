<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DummyProductSyncService;
use App\Services\VeridityProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name');

        if (request('q')) {
            $search = '%' . strtolower(request('q')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(products.name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(products.brand) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(categories.name) LIKE ?', [$search]);
            });
        }

        $products = $query->orderBy('products.id', 'desc')->get();

        return view('admin.products.index', compact('products'));
    }

    public function syncDummyProducts(DummyProductSyncService $syncService)
    {
        $count = $syncService->sync();

        return back()->with('success', "{$count} produk dummy minimarket berhasil disinkronkan.");
    }

    // 2. CREATE: Menampilkan form tambah produk
    public function create()
    {
        $categories = DB::table('categories')->orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    // 3. STORE: Menyimpan produk baru + upload foto langsung ke folder public terluar
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'brand' => 'nullable|string|max:120',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:100',
            'min_qty' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Handle upload file foto produk langsung ke public/products
        $fileName = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

            // Memindahkan berkas dan membuat folder 'products' otomatis jika belum ada
            $file->move(public_path('products'), $fileName);
        }

        DB::table('products')->insert([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'brand' => $request->brand,
            'description' => $request->description,
            'unit' => $request->unit,
            'min_qty' => $request->min_qty,
            'price' => $request->price,
            'stock' => $request->stock ?? 50,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'rating' => 4.5,
            'image' => $fileName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk grosir baru berhasil ditambahkan!');
    }

    // 4. EDIT: Menampilkan form edit produk berdasarkan ID
    public function edit($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) abort(404);
        $categories = DB::table('categories')->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    // 5. UPDATE: Memperbarui data produk dan mengganti file di folder public terluar
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'brand' => 'nullable|string|max:120',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:100',
            'min_qty' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) abort(404);

        $fileName = $product->image;

        // Jika admin mengunggah foto baru
        if ($request->hasFile('image')) {
            // Hapus foto lama langsung dari folder public/products
            if ($product->image) {
                $oldFilePath = public_path('products/' . $product->image);
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }

            $file = $request->file('image');
            $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

            // Masukkan foto baru langsung ke public/products
            $file->move(public_path('products'), $fileName);
        }

        DB::table('products')->where('id', $id)->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'brand' => $request->brand,
            'description' => $request->description,
            'unit' => $request->unit,
            'min_qty' => $request->min_qty,
            'price' => $request->price,
            'stock' => $request->stock ?? 0,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'image' => $fileName,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Data produk berhasil diperbarui!');
    }

    // 6. DELETE: Menghapus produk beserta file fotonya
    public function destroy($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) abort(404);

        // Hapus file fisik dari public/products
        if ($product->image) {
            $filePath = public_path('products/' . $product->image);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Hapus datanya dari Oracle
        DB::table('products')->where('id', $id)->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus permanen!');
    }

    // Menampilkan daftar pesanan masuk untuk divalidasi oleh Veridity AI Engine
    public function veridityOrders()
    {
        // Mengambil data pesanan, join dengan tabel users dan products
        $query = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select('orders.*', 'users.name as reseller_name', 'products.name as product_name');

        if (request('store_id')) {
            $query->where('orders.user_id', request('store_id'));
        }

        match (request('status')) {
            'accepted' => $query->where(function ($q) {
                $q->where('orders.payment_status', 'paid')
                    ->orWhere(function ($cod) {
                        $cod->where('orders.payment_method', 'cod')
                            ->where('orders.order_status', 'received');
                    });
            })->where('orders.veridity_status', '!=', 'rejected'),
            'rejected' => $query->where(function ($q) {
                $q->where('orders.payment_status', 'rejected')
                    ->orWhere('orders.veridity_status', 'rejected')
                    ->orWhere('orders.order_status', 'rejected');
            }),
            'cod' => $query->where('orders.payment_method', 'cod'),
            default => null,
        };

        $orders = $query->orderBy('orders.created_at', 'desc')->get();
        $stores = DB::table('users')
            ->where('role', 'reseller')
            ->orderBy('name')
            ->get();

        return view('admin.products.veridity', compact('orders', 'stores'));
    }

    public function stores()
    {
        $stores = DB::table('users')
            ->where('role', 'reseller')
            ->orderBy('name')
            ->get()
            ->map(function ($store) {
                $store->orders_count = DB::table('orders')->where('user_id', $store->id)->count();
                $store->paid_count = DB::table('orders')->where('user_id', $store->id)->where('payment_status', 'paid')->count();
                $store->rejected_count = DB::table('orders')->where('user_id', $store->id)->where('payment_status', 'rejected')->count();

                return $store;
            });

        return view('admin.products.stores', compact('stores'));
    }

    public function orders()
    {
        $tab = request('tab', 'packing');
        $query = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select('orders.*', 'users.name as reseller_name', 'products.name as product_name');

        match ($tab) {
            'shipped' => $query->where('orders.order_status', 'shipped'),
            'done' => $query->where('orders.order_status', 'received'),
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
                    ->orWhereRaw('LOWER(users.name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(products.name) LIKE ?', [$search]);
            });
        }

        return view('admin.products.orders', [
            'orders' => $query->orderByDesc('orders.created_at')->get(),
            'activeTab' => $tab,
            'counts' => [
                'packing' => DB::table('orders')
                    ->whereIn('order_status', ['checking', 'packing'])
                    ->where('payment_status', '!=', 'rejected')
                    ->where('veridity_status', '!=', 'rejected')
                    ->count(),
                'shipped' => DB::table('orders')->where('order_status', 'shipped')->count(),
                'done' => DB::table('orders')->where('order_status', 'received')->count(),
                'canceled' => DB::table('orders')->where(function ($q) {
                    $q->where('order_status', 'rejected')->orWhere('payment_status', 'rejected');
                })->count(),
            ],
        ]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:packing,shipped,received,rejected',
        ]);

        $order = DB::table('orders')->where('id', $id)->first();
        if (! $order) {
            abort(404);
        }

        if (in_array($order->order_status ?? '', ['received', 'rejected'], true) || ($order->payment_status ?? '') === 'rejected') {
            return back()->with('error', 'Status pesanan sudah final dan tidak dapat diubah.');
        }

        $payload = [
            'order_status' => $validated['order_status'],
            'updated_at' => now(),
        ];

        if (($order->payment_method ?? '') === 'cod' && $validated['order_status'] === 'received') {
            $payload['payment_status'] = 'paid';
            $payload['veridity_status'] = 'not_required';
            $payload['veridity_message'] = 'Pesanan COD dianggap valid setelah pesanan diterima.';
            $payload['veridity_checked_at'] = now();
        }

        if ($validated['order_status'] === 'rejected') {
            $payload['payment_status'] = 'rejected';
            $payload['veridity_status'] = 'rejected';
            $payload['veridity_message'] = 'Pesanan ditolak oleh admin operasional.';
            $payload['veridity_checked_at'] = now();
        }

        DB::table('orders')->where('id', $id)->update($payload);

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function retryVeridity($id, VeridityProofService $veridityProofService)
    {
        $order = DB::table('orders')->where('id', $id)->first();

        if (! $order) {
            abort(404);
        }

        if (! $order->proof_of_transfer) {
            return back()->with('error', 'Order ini tidak memiliki bukti pembayaran untuk dianalisis ulang.');
        }

        $result = $veridityProofService->analyze(
            $order->proof_of_transfer,
            $order->order_id_string,
            [
                'method' => $order->payment_method ?? 'unknown',
                'channel' => $order->payment_channel ?? 'unknown',
                'amount' => $order->total_amount ?? 0,
                'recipient_name' => config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.recipient_name", ''),
                'recipient_account' => config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.recipient_account", ''),
                'instruction' => $order->payment_instruction ?? '',
            ]
        );

        $orderStatus = match ($result['payment_status'] ?? null) {
            'paid' => 'packing',
            'rejected' => 'rejected',
            default => 'checking',
        };

        DB::table('orders')->where('id', $id)->update(array_merge($result, [
            'order_status' => $orderStatus,
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Analisis VERIDITY berhasil dijalankan ulang.');
    }

    public function manualAccept($id)
    {
        DB::table('orders')->where('id', $id)->update([
            'payment_status' => 'paid',
            'veridity_status' => 'verified',
            'order_status' => 'packing',
            'veridity_message' => 'Pembayaran diterima manual oleh admin. Pesanan masuk tahap dikemas.',
            'veridity_checked_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Pembayaran berhasil diterima manual.');
    }

    public function manualReject($id)
    {
        DB::table('orders')->where('id', $id)->update([
            'payment_status' => 'rejected',
            'veridity_status' => 'rejected',
            'order_status' => 'rejected',
            'veridity_message' => 'Pembayaran ditolak manual oleh admin.',
            'veridity_checked_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Pembayaran berhasil ditolak manual.');
    }

    public function showVeridityOrder($id)
    {
        $order = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->where('orders.id', $id)
            ->select('orders.*', 'users.name as reseller_name', 'products.name as product_name')
            ->first();

        if (! $order) {
            abort(404);
        }

        $validation = json_decode($order->veridity_validation_details ?? '', true);

        return view('admin.products.veridity-detail', [
            'order' => $order,
            'validation' => is_array($validation) ? $validation : ['status' => 'empty', 'summary' => 'Belum ada detail validasi.', 'checks' => []],
        ]);
    }
}
