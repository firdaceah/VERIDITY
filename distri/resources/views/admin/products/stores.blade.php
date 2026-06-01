@extends('layouts.app')
@section('title', 'Pantau Toko')

@section('styles')
    <style>
        .wrap { max-width:1100px; margin:40px auto; padding:0 24px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; margin-top:22px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:18px; padding:20px; }
        .btn { display:inline-flex; margin-top:14px; background:var(--accent); color:#fff; border-radius:12px; padding:10px 14px; text-decoration:none; font-weight:900; font-size:13px; }
        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:14px; }
        .stat { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:10px; }
    </style>
@endsection

@section('content')
    <div class="wrap">
        <div>
            <div style="font-size:11px; font-weight:900; color:var(--accent); text-transform:uppercase; letter-spacing:1px;">Monitoring Reseller</div>
            <h2 style="font-family:'Fraunces',serif; font-size:32px; margin-top:4px;">Pantau Toko Terdaftar</h2>
            <p style="font-size:13px; color:var(--muted); margin-top:4px;">Pilih toko untuk memfilter validasi nota berdasarkan reseller.</p>
        </div>

        <div class="grid">
            @foreach ($stores as $store)
                <div class="card">
                    <div style="font-size:18px; font-weight:900;">{{ $store->name }}</div>
                    <div style="font-size:12px; color:var(--muted); margin-top:4px;">{{ $store->email }}</div>
                    <div class="stats">
                        <div class="stat"><strong>{{ $store->orders_count }}</strong><div style="font-size:10px; color:var(--muted);">Order</div></div>
                        <div class="stat"><strong>{{ $store->paid_count }}</strong><div style="font-size:10px; color:var(--muted);">Paid</div></div>
                        <div class="stat"><strong>{{ $store->rejected_count }}</strong><div style="font-size:10px; color:var(--muted);">Rejected</div></div>
                    </div>
                    <a class="btn" href="{{ route('admin.products.veridity', ['store_id' => $store->id]) }}">Lihat Validasi Nota</a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
