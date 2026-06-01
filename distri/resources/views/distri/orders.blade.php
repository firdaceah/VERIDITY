@extends('layouts.app')
@section('title', 'Pesanan Saya')

@section('styles')
    <style>
        .orders-layout { max-width:980px; margin:40px auto; padding:0 24px; }
        .tabs { display:flex; gap:10px; flex-wrap:wrap; margin:20px 0; }
        .tab { background:#fff; border:1px solid var(--border); color:var(--navy); border-radius:999px; padding:10px 15px; text-decoration:none; font-size:13px; font-weight:900; }
        .tab.active { background:var(--navy); color:#fff; }
        .filter-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:14px; display:flex; gap:10px; margin-bottom:18px; }
        .o-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:24px; margin-bottom:16px; display:grid; grid-template-columns:1fr auto; gap:20px; align-items:center; }
        .badge { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:800; padding:6px 14px; border-radius:999px; text-transform:uppercase; }
        .b-checking, .b-error { background:#FEF3C7; color:#92400E; }
        .b-verified, .b-not_required { background:#DCFCE7; color:#15803D; }
        .b-rejected { background:#FEE2E2; color:#991B1B; }
        .cancel-btn { background:none; border:1px solid #FCA5A5; color:var(--red); padding:8px 16px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; }
        .meta-line { font-size:12px; color:var(--muted); margin-top:6px; line-height:1.6; }
        .veridity-note { margin-top:10px; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:12px; font-size:12px; line-height:1.6; color:var(--navy2); }
        .ship-step { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:800; padding:6px 9px; border-radius:999px; background:var(--card); color:var(--muted); border:1px solid var(--border); }
        .ship-step.on { background:var(--green-bg); color:var(--green); border-color:var(--green-border); }
        .btn { border:0; border-radius:12px; padding:12px 16px; background:var(--accent); color:#fff; font-weight:900; cursor:pointer; text-decoration:none; }
    </style>
@endsection

@section('content')
    <div class="orders-layout">
        <div>
            <h2 style="font-family:'Fraunces',serif; font-size:28px;">Pesanan Saya</h2>
            <p style="font-size:13px; color:var(--muted); margin-top:4px;">Pantau status pembayaran, pengiriman, dan hasil validasi bukti pembayaran.</p>
        </div>

        @php
            $tabs = [
                'packing' => 'Dikemas',
                'shipped' => 'Dikirim',
                'received' => 'Diterima',
                'canceled' => 'Dibatalkan',
            ];
        @endphp
        <div class="tabs">
            @foreach ($tabs as $key => $label)
                <a class="tab {{ $status === $key ? 'active' : '' }}" href="{{ route('distri.orders', ['status' => $key]) }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
            @endforeach
        </div>

        <form class="filter-card" method="GET" action="{{ route('distri.orders') }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode order, produk, atau metode pembayaran...">
            <button class="btn" type="submit">Cari</button>
            <a class="btn" href="{{ route('distri.orders', ['status' => $status]) }}" style="background:var(--card); color:var(--navy);">Reset</a>
        </form>

        @foreach (['success' => 'green', 'error' => 'red'] as $key => $color)
            @if (session($key))
                <div style="background:var(--{{ $color }}-bg); color:var(--{{ $color }}); border:1px solid var(--{{ $color }}-border); padding:14px; border-radius:12px; margin-bottom:20px; font-weight:700; font-size:14px;">{{ session($key) }}</div>
            @endif
        @endforeach

        @forelse ($orders as $order)
            @php
                $veridityStatus = $order->veridity_status ?? 'checking';
                $orderStatus = $order->order_status ?? 'checking';
                $methodLabel = config("payment_methods.{$order->payment_method}.label", $order->payment_method ?? '-');
                $channelLabel = config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.label", $order->payment_channel ?? '-');
                $statusLevel = ['packing' => 1, 'shipped' => 2, 'received' => 3][$orderStatus] ?? 0;
            @endphp
            <div class="o-card">
                <div style="display:flex; gap:20px; align-items:flex-start;">
                    <div style="width:60px; height:60px; border-radius:12px; overflow:hidden; background:#f9f9f9; border:1px solid var(--border); flex-shrink:0;">
                        @if ($order->product_image)
                            <img src="{{ asset('products/' . $order->product_image) }}" style="width:100%; height:100%; object-fit:cover;">
                        @endif
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--muted); letter-spacing:.5px;">KODE: {{ $order->order_id_string }}</div>
                        <div style="font-size:16px; font-weight:800; margin:2px 0;">{{ $order->product_name }}</div>
                        <div style="font-size:13px; color:var(--navy2);">
                            Kuantitas: <strong>{{ $order->quantity }} {{ $order->unit ?? 'unit' }}</strong> - Total:
                            <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                        </div>
                        <div class="meta-line">
                            Pembayaran: <strong>{{ $methodLabel }}</strong> via <strong>{{ $channelLabel }}</strong> - Status bayar: <strong>{{ strtoupper($order->payment_status ?? 'pending') }}</strong>
                        </div>
                        <div class="veridity-note">
                            <strong>Veridity:</strong> {{ $order->veridity_message ?? 'Menunggu analisis.' }}
                            @if ($order->veridity_score !== null)
                                <br>Skor: <strong>{{ number_format($order->veridity_score, 2) }}%</strong>
                            @endif
                            <br><a href="{{ route('distri.order.show', $order->id) }}" style="color:var(--accent); font-weight:800; text-decoration:none;">Lihat detail validasi</a>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                            <span class="ship-step {{ $statusLevel >= 1 ? 'on' : '' }}">Dikemas</span>
                            <span class="ship-step {{ $statusLevel >= 2 ? 'on' : '' }}">Dikirim</span>
                            <span class="ship-step {{ $statusLevel >= 3 ? 'on' : '' }}">Diterima</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:12px;">
                    <span class="badge b-{{ $veridityStatus }}">{{ str_replace('_', ' ', $veridityStatus) }}</span>
                    @if (in_array($veridityStatus, ['checking', 'error'], true))
                        <form action="{{ route('distri.order.delete', $order->id) }}" method="POST" class="cancel-order-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="cancel-btn btn-trigger-cancel">Batalkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:48px; background:#fff; border-radius:16px; border:1px solid var(--border);">
                <div style="font-weight:800; margin-top:12px;">Belum Ada Pesanan</div>
                <p style="font-size:13px; color:var(--muted); margin-top:4px;">Tidak ada pesanan pada filter ini.</p>
            </div>
        @endforelse
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
                        if (result.isConfirmed) form.submit();
                    });
                });
            });
        });
    </script>
@endsection
