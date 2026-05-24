<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = DB::table('products')->orderBy('id', 'desc')->get();
        return view('admin.products.index', compact('products'));
    }

    // 2. CREATE: Menampilkan form tambah produk
    public function create()
    {
        return view('admin.products.create');
    }

    // 3. STORE: Menyimpan produk baru + upload foto langsung ke folder public terluar
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:100',
            'min_qty' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Maks 5MB
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
            'name' => $request->name,
            'unit' => $request->unit,
            'min_qty' => $request->min_qty,
            'price' => $request->price,
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

        return view('admin.products.edit', compact('product'));
    }

    // 5. UPDATE: Memperbarui data produk dan mengganti file di folder public terluar
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:100',
            'min_qty' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
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
            'name' => $request->name,
            'unit' => $request->unit,
            'min_qty' => $request->min_qty,
            'price' => $request->price,
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
        $orders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select('orders.*', 'users.name as reseller_name', 'products.name as product_name')
            ->orderBy('orders.created_at', 'desc')
            ->get();

        return view('admin.products.veridity', compact('orders'));
    }
}
