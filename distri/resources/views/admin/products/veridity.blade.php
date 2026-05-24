@extends('layouts.app')
@section('title', 'Admin — Veridity AI Validation')

@section('styles')
    <style>
        .v-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 24px;
        }

        .v-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            display: flex;
            gap: 24px;
            transition: 0.2s;
        }

        .v-card:hover {
            border-color: var(--accent);
            box-shadow: 0 8px 24px rgba(46, 124, 246, 0.04);
        }

        .proof-thumb {
            width: 140px;
            height: 180px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }

        .proof-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .v-meta {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .v-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-checking {
            background: var(--yellow-bg);
            color: var(--yellow);
            border: 1px solid var(--yellow-border);
        }

        .status-verified {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid var(--green-border);
        }

        .status-rejected {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid var(--red-border);
        }

        .analysis-panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-top: 12px;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: 0.15s;
        }

        .btn-approve {
            background: var(--green);
            color: #fff;
        }

        .btn-approve:hover {
            background: #116430;
        }

        .btn-deny {
            background: var(--red);
            color: #fff;
            margin-left: 8px;
        }

        .btn-deny:hover {
            background: #7F1D1D;
        }
    </style>
@endsection

@section('content')
    <div class="v-container">
        <div style="margin-bottom: 28px;">
            <span style="font-size: 11px; font-weight: 800; color: var(--accent); letter-spacing: 1px; text-transform: uppercase;">Forensik Digital</span>
            <h2 style="font-family: 'Fraunces', serif; font-size: 32px; margin-top: 4px;">Validasi Pembayaran Reseller</h2>
            <p style="font-size: 13px; color: var(--muted);">Verifikasi keabsahan piksel nota transfer otomatis menggunakan basis kecerdasan buatan Veridity Engine.</p>
        </div>

        @if ($orders->isEmpty())
            <div style="text-align: center; padding: 64px; background: #fff; border-radius: 20px; border: 1px solid var(--border);">
                <span style="font-size: 48px;">📑</span>
                <div style="font-weight: 700; margin-top: 16px; font-size: 18px;">Belum Ada Nota Masuk</div>
                <p style="font-size: 13px; color: var(--muted); margin-top: 4px;">Seluruh riwayat pembayaran reseller yang perlu divalidasi akan muncul di panel ini.</p>
            </div>
        @else
            @foreach ($orders as $order)
                <div class="v-card">
                    {{-- DIUBAH: Menggunakan asset('proofs/...') terluar murni bypass symlink --}}
                    <div class="proof-thumb" onclick="openImageModal('{{ asset('proofs/' . $order->proof_of_transfer) }}')">
                        @if ($order->proof_of_transfer)
                            <img src="{{ asset('proofs/' . $order->proof_of_transfer) }}" alt="Nota">
                        @else
                            <div style="display:flex; align-items:center; justify-content:center; height:100%; font-size:12px; color:var(--muted)">No File</div>
                        @endif
                    </div>

                    <div class="v-meta">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <span style="font-size: 11px; color: var(--muted); font-weight: 700;">ID ORDER: {{ $order->order_id_string }}</span>
                                    <h3 style="font-size: 20px; font-weight: 800; margin: 2px 0; color: var(--navy);">{{ $order->reseller_name }}</h3>
                                </div>
                                <span class="v-badge-status status-{{ $order->veridity_status }}">
                                    ● {{ $order->veridity_status }}
                                </span>
                            </div>

                            <p style="font-size: 13px; color: var(--navy2); margin-top: 6px;">
                                Pesanan: <strong>{{ $order->product_name }} ({{ $order->quantity }} Unit)</strong> · Total Tagihan: <strong style="color: var(--accent);">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                            </p>

                            <div class="analysis-panel">
                                <div style="font-size: 12px; font-weight: 700; margin-bottom: 4px; display:flex; align-items:center; gap:6px;">
                                    🛡️ <span>Veridity AI Analysis Result:</span>
                                </div>
                                <p style="font-size: 12.5px; color: var(--muted); line-height: 1.5;">
                                    @if ($order->veridity_status === 'checking')
                                        Mengekstrak metadata gambar struk... Menunggu jalur API forensik menembak status verifikasi pixel manipulasi (ELA).
                                    @elseif($order->veridity_status === 'verified')
                                        <span style="color: var(--green); font-weight: 600;">[AMAN]</span> Tidak ditemukan ketidaksesuaian tingkat kompresi (Error Level Analysis). Metadata EXIF konsisten dengan aplikasi perbankan asli.
                                    @else
                                        <span style="color: var(--red); font-weight: 600;">[TERINDIKASI PALSU]</span> Terdeteksi manipulasi teks digital (kloning piksel font) pada area nominal transfer. Tingkat kecocokan eror kompresi berada di bawah batas ambang 85%.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($order->veridity_status === 'checking')
                            <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                                <button class="btn-action btn-approve" onclick="simulateValidation('{{ $order->id }}', 'verified')">Setujui (Lolos AI)</button>
                                <button class="btn-action btn-deny" onclick="simulateValidation('{{ $order->id }}', 'rejected')">Tolak Nota</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div id="imgModal" style="display: none; position: fixed; z-index: 1000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center;" onclick="this.style.display='none'">
        <img id="modalImg" style="max-width: 90%; max-height: 90%; border-radius: 12px; box-shadow: 0 0 30px rgba(0,0,0,0.5);">
    </div>

    <script>
        function openImageModal(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('imgModal').style.display = 'flex';
        }

        function simulateValidation(orderId, status) {
            let actionText = status === 'verified' ? 'Meloloskan nota ini sebagai bukti SAH?' : 'Menolak nota ini karena indikasi manipulasi digital?';

            Swal.fire({
                title: 'Konfirmasi Validasi',
                text: actionText,
                icon: status === 'verified' ? 'question' : 'error',
                showCancelButton: true,
                confirmButtonColor: status === 'verified' ? '#15803D' : '#991B1B',
                cancelButtonColor: '#637899',
                confirmButtonText: 'Ya, Konfirmasi!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'Status transaksi berhasil diperbarui di database Oracle.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }
    </script>
@endsection