<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; // Disiapkan untuk jembatan integrasi API nanti

class OrderController extends Controller
{
    // 1. Menampilkan Halaman Dashboard / Beranda Utama Reseller
    public function landing()
    {
        return view('distri.landing');
    }

    // 2. Menampilkan Halaman Katalog Reseller dari Oracle
    public function catalog()
    {
        $products = DB::table('products')->orderBy('id', 'desc')->get();
        return view('distri.catalog', compact('products'));
    }

    // 3. Menampilkan Form Checkout Transaksi
    public function checkout($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) abort(404);

        return view('distri.checkout', compact('product'));
    }

    // 4. STORE ORDER: Memproses pesanan + Upload Nota Langsung ke Folder Public Terluar
    public function storeOrder(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'total_amount' => 'required|numeric',
            'proof_of_transfer' => 'required|image|mimes:jpg,jpeg,png|max:10120'
        ]);

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

        // Simpan Data rill ke tabel ORDERS skema Oracle
        DB::table('orders')->insert([
            'order_id_string' => $orderId,
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'total_amount' => $request->total_amount,
            'proof_of_transfer' => $fileName,
            'veridity_status' => 'checking', // Status awal mengambang sebelum diproses engine AI
            'created_at' => now(),
            'updated_at' => now()
        ]);

        /* |--------------------------------------------------------------------------
        | POS INTEGRASI API VERIDITY ENGINE
        |--------------------------------------------------------------------------
        | Nanti di titik ini kita selipkan Http::attach() buat ngelempar file 
        | public_path('proofs/' . $fileName) ke endpoint backend veridity-laravel.
        |
        */

        return redirect()->route('distri.orders')->with('success', 'Pesanan berhasil dikirim! Nota transfer Anda sedang dianalisis oleh Veridity AI Engine.');
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
