@extends('layouts.app')
@section('title', 'Pesanan Saya')

@section('styles')
    <style>
        .orders-layout { max-width: 980px; margin: 40px auto; padding: 0 24px; }
        .o-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 16px; display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center; }
        .badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 999px; text-transform: uppercase; }
        .b-checking, .b-error { background: #FEF3C7; color: #92400E; }
        .b-verified, .b-not_required { background: #DCFCE7; color: #15803D; }
        .b-rejected { background: #FEE2E2; color: #991B1B; }
        .cancel-btn { background: none; border: 1px solid #FCA5A5; color: var(--red); padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; }
        .cancel-btn:hover { background: #FEF2F2; }
        .meta-line { font-size: 12px; color: var(--muted); margin-top: 6px; line-height: 1.6; }
        .veridity-note { margin-top: 10px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 12px; font-size: 12px; line-height: 1.6; color: var(--navy2); }
    </style>
@endsection

@section('content')
    <div class="orders-layout">
        <div style="margin-bottom: 24px;">
            <h2 style="font-family: 'Fraunces', serif; font-size: 28px;">Pesanan Saya</h2>
            <p style="font-size: 13px; color: var(--muted);">Pantau status pembayaran dan hasil validasi bukti pembayaran dari Veridity Engine.</p>
        </div>

        @foreach (['success' => 'green', 'error' => 'red'] as $key => $color)
            @if (session($key))
                <div style="background: var(--{{ $color }}-bg); color: var(--{{ $color }}); border: 1px solid var(--{{ $color }}-border); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 14px;">
                    {{ session($key) }}
                </div>
            @endif
        @endforeach

        @if ($orders->isEmpty())
            <div style="text-align: center; padding: 48px; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
                <div style="font-weight: 700; margin-top: 12px;">Belum Ada Riwayat Transaksi</div>
                <p style="font-size: 13px; color: var(--muted); margin-top: 4px;">Silakan menuju katalog untuk mulai memesan barang grosir.</p>
            </div>
        @else
            @foreach ($orders as $order)
                @php
                    $status = $order->veridity_status ?? 'checking';
                    $methodLabel = config("payment_methods.{$order->payment_method}.label", $order->payment_method ?? '-');
                    $channelLabel = config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.label", $order->payment_channel ?? '-');
                @endphp
                <div class="o-card">
                    <div style="display: flex; gap: 20px; align-items: flex-start;">
                        <div style="width: 60px; height: 60px; border-radius: 12px; overflow: hidden; background: #f9f9f9; border: 1px solid var(--border); flex-shrink: 0;">
                            @if ($order->product_image)
                                <img src="{{ asset('products/' . $order->product_image) }}" style="width:100%; height:100%; object-fit:cover;">
                            @endif
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--muted); letter-spacing: 0.5px;">KODE: {{ $order->order_id_string }}</div>
                            <div style="font-size: 16px; font-weight: 700; margin: 2px 0;">{{ $order->product_name }}</div>
                            <div style="font-size: 13px; color: var(--navy2);">
                                Kuantitas: <strong>{{ $order->quantity }} {{ $order->unit ?? 'unit' }}</strong> · Total:
                                <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                            </div>
                            <div class="meta-line">
                                Pembayaran: <strong>{{ $methodLabel }}</strong> via <strong>{{ $channelLabel }}</strong> · Status bayar: <strong>{{ strtoupper($order->payment_status ?? 'pending') }}</strong>
                            </div>
                            <div class="veridity-note">
                                <strong>Veridity:</strong> {{ $order->veridity_message ?? 'Menunggu analisis.' }}
                                @if ($order->veridity_score !== null)
                                    <br>Skor: <strong>{{ number_format($order->veridity_score, 2) }}%</strong>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                        <span class="badge b-{{ $status }}">● {{ str_replace('_', ' ', $status) }}</span>

                        @if (in_array($status, ['checking', 'error'], true))
                            <form action="{{ route('distri.order.delete', $order->id) }}" method="POST" class="cancel-order-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="cancel-btn btn-trigger-cancel">Batalkan</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.btn-trigger-cancel').forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('.cancel-order-form');
                    Swal.fire({
                        title: 'Batalkan Pesanan?',
                        text: 'Data antrean pesanan grosir ini akan dihapus permanen dari sistem.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#991B1B',
                        cancelButtonColor: '#637899',
                        confirmButtonText: 'Ya, Batalkan',
                        cancelButtonText: 'Kembali',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection
