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
        $products = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->orderBy('products.id', 'desc')
            ->get();
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

        DB::table('orders')->where('id', $id)->update(array_merge($result, [
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Analisis VERIDITY berhasil dijalankan ulang.');
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
