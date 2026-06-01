@extends('layouts.app')
@section('title', 'Admin - Pantau Pesanan')

@section('styles')
    <style>
        .admin-container { max-width:1120px; margin:40px auto; padding:0 24px; }
        .tabs { display:flex; gap:10px; flex-wrap:wrap; margin:20px 0; }
        .tab { background:#fff; border:1px solid var(--border); color:var(--navy); border-radius:999px; padding:10px 15px; text-decoration:none; font-size:13px; font-weight:900; }
        .tab.active { background:var(--navy); color:#fff; }
        .filter-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:16px; margin-bottom:20px; display:flex; gap:10px; align-items:end; }
        .order-card { background:#fff; border:1px solid var(--border); border-radius:18px; padding:20px; margin-bottom:14px; display:grid; grid-template-columns:1fr 280px; gap:18px; }
        .badge { display:inline-flex; padding:6px 12px; border-radius:999px; font-size:11px; font-weight:900; text-transform:uppercase; background:var(--card); color:var(--muted); border:1px solid var(--border); }
        .badge.packing, .badge.shipped, .badge.received { background:var(--green-bg); color:var(--green); border-color:var(--green-border); }
        .badge.rejected { background:var(--red-bg); color:var(--red); border-color:var(--red-border); }
        .btn { border:0; border-radius:12px; padding:12px 16px; background:var(--accent); color:#fff; font-weight:900; cursor:pointer; text-decoration:none; text-align:center; }
        .btn:disabled { opacity:.45; cursor:not-allowed; }
        .select { width:100%; padding:12px 14px; border:1px solid var(--border); border-radius:12px; font-family:inherit; color:var(--navy); background:#fff; }
    </style>
@endsection

@section('content')
    <div class="admin-container">
        <div>
            <span style="font-size:11px; font-weight:900; color:var(--accent); letter-spacing:1px; text-transform:uppercase;">Operasional Toko</span>
            <h2 style="font-family:'Fraunces',serif; font-size:32px; margin-top:4px;">Pantau Pesanan</h2>
            <p style="font-size:13px; color:var(--muted); margin-top:4px;">Pisahkan pesanan aktif, selesai, dan batal agar proses pengiriman mudah dipantau.</p>
        </div>

        @php
            $tabs = [
                'packing' => 'Dikemas',
                'shipped' => 'Dikirim',
                'done' => 'Selesai',
                'canceled' => 'Batal',
            ];
        @endphp
        <div class="tabs">
            @foreach ($tabs as $key => $label)
                <a class="tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('admin.orders.index', ['tab' => $key]) }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
            @endforeach
        </div>

        @if (session('success'))
            <div style="background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); padding:14px; border-radius:12px; margin-bottom:18px; font-weight:800; font-size:14px;">{{ session('success') }}</div>
        @endif

        <form class="filter-card" method="GET" action="{{ route('admin.orders.index') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div style="flex:1;">
                <label style="font-size:11px; color:var(--muted); font-weight:900; text-transform:uppercase;">Cari Pesanan</label>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Kode order, reseller, atau produk...">
            </div>
            <button class="btn" type="submit">Cari</button>
            <a class="btn" href="{{ route('admin.orders.index', ['tab' => $activeTab]) }}" style="background:var(--card); color:var(--navy);">Reset</a>
        </form>

        @forelse ($orders as $order)
            @php
                $orderStatus = $order->order_status ?? 'checking';
                $isFinal = in_array($orderStatus, ['received', 'rejected'], true) || ($order->payment_status ?? '') === 'rejected';
                $methodLabel = config("payment_methods.{$order->payment_method}.label", $order->payment_method ?? '-');
                $channelLabel = config("payment_methods.{$order->payment_method}.channels.{$order->payment_channel}.label", $order->payment_channel ?? '-');
            @endphp
            <div class="order-card">
                <div>
                    <div style="display:flex; justify-content:space-between; gap:14px; align-items:start;">
                        <div>
                            <div style="font-size:11px; color:var(--muted); font-weight:900;">{{ $order->order_id_string }}</div>
                            <h3 style="font-size:20px; font-weight:900; margin-top:3px;">{{ $order->reseller_name }}</h3>
                        </div>
                        <span class="badge {{ $orderStatus }}">{{ str_replace('_', ' ', $orderStatus) }}</span>
                    </div>
                    <p style="font-size:13px; color:var(--navy2); line-height:1.8; margin-top:12px;">
                        Produk: <strong>{{ $order->product_name }}</strong> ({{ $order->quantity }} unit)<br>
                        Total: <strong style="color:var(--accent);">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong><br>
                        Pembayaran: <strong>{{ $methodLabel }}</strong> via <strong>{{ $channelLabel }}</strong> - <strong>{{ strtoupper($order->payment_status ?? 'pending') }}</strong>
                    </p>
                    @if (($order->payment_method ?? '') === 'cod')
                        <div style="background:var(--yellow-bg); border:1px solid var(--yellow-border); color:var(--yellow); border-radius:12px; padding:12px; margin-top:10px; font-size:12px; font-weight:800;">
                            COD dianggap valid setelah pesanan diterima. Status dikirim berarti barang masih dalam perjalanan dan belum dibayar.
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}" style="display:grid; gap:10px; align-content:start;">
                    @csrf
                    @method('PATCH')
                    <label style="font-size:11px; color:var(--muted); font-weight:900; text-transform:uppercase;">Ubah Status Pesanan</label>
                    <select class="select" name="order_status" @disabled($isFinal)>
                        <option value="packing" @selected($orderStatus === 'packing' || $orderStatus === 'checking')>Dikemas</option>
                        <option value="shipped" @selected($orderStatus === 'shipped')>Dikirim</option>
                        <option value="received" @selected($orderStatus === 'received')>Diterima</option>
                        <option value="rejected" @selected($orderStatus === 'rejected')>Batal</option>
                    </select>
                    <button class="btn" type="submit" @disabled($isFinal)>Simpan Status</button>
                    <a href="{{ route('admin.products.veridity.show', $order->id) }}" style="text-align:center; color:var(--accent); text-decoration:none; font-size:12px; font-weight:900;">Detail validasi nota</a>
                </form>
            </div>
        @empty
            <div style="background:#fff; border:1px solid var(--border); border-radius:18px; padding:42px; text-align:center; color:var(--muted);">Belum ada pesanan pada tab ini.</div>
        @endforelse
    </div>
@endsection
