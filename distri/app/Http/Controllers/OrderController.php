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
        $paymentMethods = config('payment_methods');

        return view('distri.checkout', compact('product', 'paymentMethods'));
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
            'product_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_channel' => 'required|string',
            'proof_of_transfer' => ($requiresProof ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png|max:10120',
        ]);

        if (! $selectedMethod || ! $selectedChannel) {
            return back()->withErrors(['payment_method' => 'Metode pembayaran tidak valid.'])->withInput();
        }

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
                    'amount' => $request->total_amount,
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
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'total_amount' => $request->total_amount,
            'proof_of_transfer' => $fileName,
            'payment_method' => $methodKey,
            'payment_channel' => $channelKey,
            'payment_status' => $veridityData['payment_status'],
            'payment_instruction' => $selectedChannel['instruction'],
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
            'validation' => $this->decodeValidationDetails($order->veridity_validation_details ?? null),
        ]);
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
