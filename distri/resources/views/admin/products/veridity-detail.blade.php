@extends('layouts.app')
@section('title', 'Detail Validasi Nota')

@section('styles')
    <style>
        .detail-wrap { max-width: 1100px; margin: 40px auto; padding: 0 24px; display: grid; gap: 18px; }
        .detail-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px; }
        .badge { display:inline-flex; padding:7px 14px; border-radius:999px; font-size:12px; font-weight:800; text-transform:uppercase; }
        .b-verified, .b-not_required { background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); }
        .b-rejected { background:var(--red-bg); color:var(--red); border:1px solid var(--red-border); }
        .b-review_required, .b-checking, .b-error { background:var(--yellow-bg); color:var(--yellow); border:1px solid var(--yellow-border); }
        .btn-retry { background:var(--accent); color:#fff; border:0; padding:11px 18px; border-radius:10px; font-weight:800; cursor:pointer; }
        .btn-accept { background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); padding:11px 18px; border-radius:10px; font-weight:800; cursor:pointer; }
        .btn-reject { background:var(--red-bg); color:var(--red); border:1px solid var(--red-border); padding:11px 18px; border-radius:10px; font-weight:800; cursor:pointer; }
        button:disabled { opacity:.45; cursor:not-allowed; }
    </style>
@endsection

@section('content')
    @php
        $status = $order->veridity_status ?? 'checking';
        $orderLocked = in_array($order->payment_status ?? '', ['paid', 'rejected'], true)
            || in_array($order->veridity_status ?? '', ['verified', 'rejected', 'not_required'], true);
        $methodLabel = config("payment_methods.{$order->payment_method}.label", $order->payment_method ?? '-');
        $channelLabel = config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.label", $order->payment_channel ?? '-');
    @endphp

    <div class="detail-wrap">
        <a href="{{ route('admin.products.veridity') }}" style="text-decoration:none; color:var(--accent); font-size:13px; font-weight:800;">&larr; Kembali ke validasi nota</a>

        <div class="detail-card">
            <div style="display:flex; justify-content:space-between; gap:18px; align-items:flex-start;">
                <div>
                    <div style="font-size:11px; color:var(--muted); font-weight:800;">ORDER {{ $order->order_id_string }}</div>
                    <h2 style="font-family:'Fraunces',serif; font-size:30px; margin-top:3px;">{{ $order->reseller_name }}</h2>
                    <p style="font-size:13px; color:var(--navy2); line-height:1.8; margin-top:8px;">
                        Produk: <strong>{{ $order->product_name }}</strong><br>
                        Total: <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong><br>
                        Pembayaran: <strong>{{ $methodLabel }}</strong> via <strong>{{ $channelLabel }}</strong><br>
                        Instruksi: {{ $order->payment_instruction ?? '-' }}
                    </p>
                </div>
                <span class="badge b-{{ $status }}">{{ str_replace('_', ' ', $status) }}</span>
            </div>
        </div>

        <div class="detail-card">
            <div style="display:grid; grid-template-columns:220px 1fr; gap:20px;">
                <div style="border:1px solid var(--border); border-radius:14px; overflow:hidden; min-height:220px; background:var(--card);">
                    @if ($order->proof_of_transfer)
                        <img src="{{ asset('proofs/' . $order->proof_of_transfer) }}" style="width:100%; height:100%; object-fit:cover;" alt="Nota">
                    @else
                        <div style="padding:20px; font-size:13px; color:var(--muted);">Tidak ada bukti manual.</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:11px; color:var(--muted); font-weight:800; letter-spacing:1px; text-transform:uppercase;">Ringkasan VERIDITY</div>
                    <p style="font-size:14px; color:var(--navy2); line-height:1.7; margin-top:10px;">{{ $order->veridity_message ?? 'Menunggu analisis.' }}</p>
                    @if ($order->veridity_score !== null)
                        <p style="font-size:13px; margin-top:8px;">Skor forensik: <strong>{{ number_format($order->veridity_score, 2) }}%</strong></p>
                    @endif
                    @if ($order->veridity_audit_id)
                        <p style="font-size:13px; margin-top:8px;">ID audit VERIDITY: <strong>#VRD-{{ $order->veridity_audit_id }}</strong></p>
                    @endif
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
                        @if ($order->proof_of_transfer)
                            <form method="POST" action="{{ route('admin.products.veridity.retry', $order->id) }}">
                                @csrf
                                <button class="btn-retry" type="submit">Retry Analisis VERIDITY</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.products.veridity.manual-accept', $order->id) }}">
                            @csrf
                            <button class="btn-accept" type="submit" @disabled($orderLocked)>Terima Manual</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.veridity.manual-reject', $order->id) }}">
                            @csrf
                            <button class="btn-reject" type="submit" @disabled($orderLocked)>Tolak Manual</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.veridity-validation-checks', ['validation' => $validation, 'showOcr' => true])
    </div>
@endsection
