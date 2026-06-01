@extends('layouts.app')
@section('title', 'Detail Pesanan')

@section('styles')
    <style>
        .detail-wrap { max-width: 980px; margin: 40px auto; padding: 0 24px; display: grid; gap: 18px; }
        .detail-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px; }
        .badge { display:inline-flex; padding:7px 14px; border-radius:999px; font-size:12px; font-weight:800; text-transform:uppercase; }
        .b-verified, .b-not_required { background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); }
        .b-rejected { background:var(--red-bg); color:var(--red); border:1px solid var(--red-border); }
        .b-review_required, .b-checking, .b-error { background:var(--yellow-bg); color:var(--yellow); border:1px solid var(--yellow-border); }
    </style>
@endsection

@section('content')
    @php
        $status = $order->veridity_status ?? 'checking';
        $methodLabel = config("payment_methods.{$order->payment_method}.label", $order->payment_method ?? '-');
        $channelLabel = config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.label", $order->payment_channel ?? '-');
    @endphp

    <div class="detail-wrap">
        <a href="{{ route('distri.orders') }}" style="text-decoration:none; color:var(--accent); font-size:13px; font-weight:800;">&larr; Kembali ke riwayat</a>

        @if (session('success'))
            <div style="background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); padding:14px; border-radius:12px; font-weight:700; font-size:14px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="detail-card">
            <div style="display:flex; justify-content:space-between; gap:18px; align-items:flex-start;">
                <div>
                    <div style="font-size:11px; color:var(--muted); font-weight:800;">KODE PESANAN</div>
                    <h2 style="font-family:'Fraunces',serif; font-size:30px; margin-top:3px;">{{ $order->order_id_string }}</h2>
                    <p style="font-size:13px; color:var(--navy2); line-height:1.8; margin-top:8px;">
                        Produk: <strong>{{ $order->product_name }}</strong><br>
                        Total: <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong><br>
                        Pembayaran: <strong>{{ $methodLabel }}</strong> via <strong>{{ $channelLabel }}</strong>
                    </p>
                </div>
                <span class="badge b-{{ $status }}">{{ str_replace('_', ' ', $status) }}</span>
            </div>
        </div>

        <div class="detail-card">
            <div style="font-size:11px; color:var(--muted); font-weight:800; letter-spacing:1px; text-transform:uppercase;">Ringkasan VERIDITY</div>
            <p style="font-size:14px; color:var(--navy2); line-height:1.7; margin-top:10px;">{{ $order->veridity_message ?? 'Menunggu analisis.' }}</p>
            @if ($order->veridity_score !== null)
                <p style="font-size:13px; margin-top:8px;">Skor forensik: <strong>{{ number_format($order->veridity_score, 2) }}%</strong></p>
            @endif
        </div>

        @include('partials.veridity-validation-checks', ['validation' => $validation])
    </div>
@endsection
