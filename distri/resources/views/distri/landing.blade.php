@extends('layouts.app')
@section('title', 'Distri Mart')

@section('styles')
    <style>
        .home-wrap { max-width:1220px; margin:30px auto; padding:0 24px 48px; }
        .hero { background:linear-gradient(135deg,var(--navy),var(--navy3)); color:#fff; border-radius:28px; padding:36px; display:grid; grid-template-columns:1.3fr .7fr; gap:26px; align-items:center; }
        .hero h1 { font-family:'Fraunces',serif; font-size:46px; line-height:1.08; }
        .btn { background:#fff; color:var(--navy); border-radius:14px; padding:13px 20px; font-weight:900; text-decoration:none; display:inline-block; margin-top:22px; }
        .mini-panel { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.16); border-radius:20px; padding:22px; }
        .feature-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin:20px 0; }
        .feature { background:#fff; border:1px solid var(--border); border-radius:18px; padding:18px; }
        .prod-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:18px; }
        .pcard { background:#fff; border:1px solid var(--border); border-radius:18px; overflow:hidden; display:flex; flex-direction:column; min-height:360px; }
        .pimg { height:170px; background:var(--card); display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .pimg img { width:100%; height:100%; object-fit:cover; }
        .pbody { padding:16px; display:flex; flex-direction:column; gap:8px; flex:1; }
        .badge { width:max-content; background:#EEF4FF; color:var(--accent); border-radius:999px; padding:4px 9px; font-size:10px; font-weight:800; }
        .pname { font-size:15px; font-weight:800; color:var(--navy); line-height:1.35; min-height:42px; }
        .muted { color:var(--muted); font-size:12px; }
        .price { margin-top:auto; font-size:19px; color:var(--accent); font-weight:900; }
        .cart-btn { width:100%; border:0; border-radius:12px; background:var(--navy); color:#fff; padding:11px; font-weight:800; cursor:pointer; text-decoration:none; display:inline-flex; justify-content:center; align-items:center; }
    </style>
@endsection

@section('content')
    <div class="home-wrap">
        <div class="hero">
            <div>
                <div style="font-size:11px; letter-spacing:1px; text-transform:uppercase; font-weight:900; color:#9cc7ff;">Minimarket Reseller + VERIDITY</div>
                <h1>Belanja stok toko, nota dicek otomatis.</h1>
                <p style="color:rgba(255,255,255,.72); line-height:1.7; margin-top:14px;">Distri Mart membantu reseller membeli produk minimarket, memilih pembayaran, dan memvalidasi bukti transfer lewat VERIDITY.</p>
                <a href="{{ route('distri.catalog') }}" class="btn">Mulai Belanja</a>
            </div>
            <div class="mini-panel">
                <div style="font-size:13px; color:rgba(255,255,255,.7);">Status perlindungan</div>
                <div style="font-size:38px; font-weight:900; margin:8px 0;">99.8%</div>
                <div style="font-size:13px; color:rgba(255,255,255,.7); line-height:1.6;">Simulasi validasi nota aman untuk demo framework, basis data, dan integrasi VERIDITY.</div>
            </div>
        </div>

        <div class="feature-row">
            <div class="feature"><strong>Katalog minimarket</strong><p style="font-size:13px; color:var(--muted); margin-top:8px;">Produk grocery, perawatan, dapur, dan rumah tangga.</p></div>
            <div class="feature"><strong>Keranjang belanja</strong><p style="font-size:13px; color:var(--muted); margin-top:8px;">Checkout banyak item dalam satu transaksi.</p></div>
            <div class="feature"><strong>Validasi nota</strong><p style="font-size:13px; color:var(--muted); margin-top:8px;">Cek forensik gambar dan isi nota pembayaran.</p></div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:end; margin:28px 0 14px;">
            <div>
                <h2 style="font-family:'Fraunces',serif; font-size:28px;">Promo dan rekomendasi</h2>
                <p style="font-size:13px; color:var(--muted);">Produk diskon tertinggi dari katalog.</p>
            </div>
            <a href="{{ route('distri.catalog') }}" style="color:var(--accent); text-decoration:none; font-weight:900;">Lihat semua</a>
        </div>

        <div class="prod-grid">
            @foreach ($featured as $product)
                <div class="pcard">
                    <div class="pimg">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @elseif ($product->image)
                            <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}">
                        @endif
                    </div>
                    <div class="pbody">
                        <span class="badge">{{ $product->category_name ?? 'Minimarket' }}</span>
                        <div class="pname">{{ $product->name }}</div>
                        <div class="muted">{{ $product->brand ?? 'Distri' }} - Stok {{ $product->stock ?? 0 }} - Rating {{ number_format($product->rating ?? 0, 1) }}</div>
                        @if (($product->discount_percentage ?? 0) > 0)
                            <div class="muted" style="color:var(--red); font-weight:800;">Diskon {{ number_format($product->discount_percentage, 0) }}%</div>
                        @endif
                        <div class="price">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                        <div style="display:grid; grid-template-columns:1fr auto; gap:8px;">
                            <a href="{{ route('distri.product.show', $product->id) }}" class="cart-btn">Detail</a>
                            <form method="POST" action="{{ route('distri.cart.add') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button class="cart-btn" type="submit" title="Tambah ke keranjang" aria-label="Tambah ke keranjang">
                                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.96a2 2 0 0 0 1.95-1.57l1.35-6.43H5.12"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
