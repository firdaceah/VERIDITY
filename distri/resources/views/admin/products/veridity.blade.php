@extends('layouts.app')
@section('title', 'Admin - Veridity AI Validation')

@section('styles')
    <style>
        .v-container { max-width: 1100px; margin: 40px auto; padding: 0 24px; }
        .v-card { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; display: flex; gap: 24px; transition: 0.2s; }
        .v-card:hover { border-color: var(--accent); box-shadow: 0 8px 24px rgba(46, 124, 246, 0.04); }
        .proof-thumb { width: 140px; height: 180px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; cursor: pointer; position: relative; flex-shrink: 0; }
        .proof-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .v-meta { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .v-badge-status { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 800; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-checking, .status-error { background: var(--yellow-bg); color: var(--yellow); border: 1px solid var(--yellow-border); }
        .status-verified, .status-not_required { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
        .status-rejected { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
        .analysis-panel { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-top: 12px; }
        .btn-action { padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; transition: 0.15s; }
        .btn-retry { background: var(--accent); color: #fff; }
        .btn-retry:hover { background: var(--navy3); }
        .btn-accept { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
        .btn-reject { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
        .btn-action:disabled { opacity:.45; cursor:not-allowed; }
    </style>
@endsection

@section('content')
    <div class="v-container">
        <div style="margin-bottom: 28px;">
            <span style="font-size: 11px; font-weight: 800; color: var(--accent); letter-spacing: 1px; text-transform: uppercase;">Forensik Digital</span>
            <h2 style="font-family: 'Fraunces', serif; font-size: 32px; margin-top: 4px;">Validasi Pembayaran Reseller</h2>
            <p style="font-size: 13px; color: var(--muted);">Pantau hasil analisis bukti pembayaran yang dikirim otomatis ke VERIDITY.</p>
        </div>

        <form method="GET" action="{{ route('admin.products.veridity') }}" style="background:#fff; border:1px solid var(--border); border-radius:16px; padding:16px; margin-bottom:20px; display:flex; gap:12px; align-items:end;">
            <div style="flex:1;">
                <label style="font-size:11px; color:var(--muted); font-weight:900; text-transform:uppercase;">Filter Toko</label>
                <select name="store_id" class="form-control" style="margin-top:6px;">
                    <option value="">Semua toko</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) request('store_id') === (string) $store->id)>{{ $store->name }} - {{ $store->email }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:180px;">
                <label style="font-size:11px; color:var(--muted); font-weight:900; text-transform:uppercase;">Status</label>
                <select name="status" class="form-control" style="margin-top:6px;">
                    <option value="">Semua</option>
                    <option value="accepted" @selected(request('status') === 'accepted')>Accepted</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    <option value="cod" @selected(request('status') === 'cod')>Proses COD</option>
                </select>
            </div>
            <button type="submit" class="btn-action btn-retry">Terapkan</button>
            <a href="{{ route('admin.products.veridity') }}" class="btn-action" style="text-decoration:none; background:var(--card); color:var(--navy);">Reset</a>
        </form>

        @foreach (['success' => 'green', 'error' => 'red'] as $key => $color)
            @if (session($key))
                <div style="background: var(--{{ $color }}-bg); color: var(--{{ $color }}); border: 1px solid var(--{{ $color }}-border); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 14px;">
                    {{ session($key) }}
                </div>
            @endif
        @endforeach

        @if ($orders->isEmpty())
            <div style="text-align: center; padding: 64px; background: #fff; border-radius: 20px; border: 1px solid var(--border);">
                <div style="font-weight: 700; margin-top: 16px; font-size: 18px;">Belum Ada Nota Masuk</div>
                <p style="font-size: 13px; color: var(--muted); margin-top: 4px;">Riwayat pembayaran reseller akan muncul di panel ini.</p>
            </div>
        @else
            @foreach ($orders as $order)
                @php
                    $status = $order->veridity_status ?? 'checking';
                    $orderLocked = in_array($order->payment_status ?? '', ['paid', 'rejected'], true)
                        || in_array($order->veridity_status ?? '', ['verified', 'rejected', 'not_required'], true);
                    $methodLabel = config("payment_methods.{$order->payment_method}.label", $order->payment_method ?? '-');
                    $channelLabel = config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.label", $order->payment_channel ?? '-');
                @endphp
                <div class="v-card">
                    <div class="proof-thumb" @if ($order->proof_of_transfer) onclick="openImageModal('{{ asset('proofs/' . $order->proof_of_transfer) }}')" @endif>
                        @if ($order->proof_of_transfer)
                            <img src="{{ asset('proofs/' . $order->proof_of_transfer) }}" alt="Nota">
                        @else
                            <div style="display:flex; align-items:center; justify-content:center; height:100%; font-size:12px; color:var(--muted); text-align:center; padding:12px;">Tidak ada bukti manual</div>
                        @endif
                    </div>

                    <div class="v-meta">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                                <div>
                                    <span style="font-size: 11px; color: var(--muted); font-weight: 700;">ID ORDER: {{ $order->order_id_string }}</span>
                                    <h3 style="font-size: 20px; font-weight: 800; margin: 2px 0; color: var(--navy);">{{ $order->reseller_name }}</h3>
                                </div>
                                <span class="v-badge-status status-{{ $status }}">● {{ str_replace('_', ' ', $status) }}</span>
                            </div>

                            <p style="font-size: 13px; color: var(--navy2); margin-top: 6px; line-height:1.6;">
                                Pesanan: <strong>{{ $order->product_name }} ({{ $order->quantity }} Unit)</strong> ·
                                Total: <strong style="color: var(--accent);">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong><br>
                                Pembayaran: <strong>{{ $methodLabel }}</strong> via <strong>{{ $channelLabel }}</strong> ·
                                Status bayar: <strong>{{ strtoupper($order->payment_status ?? 'pending') }}</strong>
                            </p>

                            <div class="analysis-panel">
                                <div style="font-size: 12px; font-weight: 700; margin-bottom: 4px;">Veridity AI Analysis Result</div>
                                <p style="font-size: 12.5px; color: var(--muted); line-height: 1.5;">
                                    {{ $order->veridity_message ?? 'Menunggu analisis.' }}
                                    @if ($order->veridity_score !== null)
                                        <br>Skor analisis: <strong>{{ number_format($order->veridity_score, 2) }}%</strong>
                                    @endif
                                    @if ($order->veridity_audit_id)
                                        <br>ID audit VERIDITY: <strong>#VRD-{{ $order->veridity_audit_id }}</strong>
                                    @endif
                                    <br><a href="{{ route('admin.products.veridity.show', $order->id) }}" style="color:var(--accent); font-weight:800; text-decoration:none;">Lihat detail validasi nota</a>
                                </p>
                            </div>
                        </div>

                        @if (in_array($status, ['checking', 'error'], true) && $order->proof_of_transfer)
                            <div style="margin-top: 16px; display: flex; justify-content: flex-end; gap:10px; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('admin.products.veridity.retry', $order->id) }}">
                                    @csrf
                                    <button class="btn-action btn-retry" type="submit">Retry Analisis VERIDITY</button>
                                </form>
                                <form method="POST" action="{{ route('admin.products.veridity.manual-accept', $order->id) }}">
                                    @csrf
                                    <button class="btn-action btn-accept" type="submit" @disabled($orderLocked)>Terima Manual</button>
                                </form>
                                <form method="POST" action="{{ route('admin.products.veridity.manual-reject', $order->id) }}">
                                    @csrf
                                    <button class="btn-action btn-reject" type="submit" @disabled($orderLocked)>Tolak Manual</button>
                                </form>
                            </div>
                        @elseif (($order->payment_method ?? '') === 'cod')
                            <div style="margin-top: 16px; display: flex; justify-content: flex-end; gap:10px; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('admin.products.veridity.manual-accept', $order->id) }}">
                                    @csrf
                                    <button class="btn-action btn-accept" type="submit" @disabled($orderLocked)>Proses COD</button>
                                </form>
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
    </script>
@endsection
