@extends('layouts.app')
@section('title', 'Belanja Minimarket')

@section('styles')
    <style>
        .shop-wrap { max-width: 1220px; margin: 32px auto; padding: 0 24px 48px; }
        .shop-hero { background: linear-gradient(135deg, var(--navy), var(--navy3)); color:#fff; border-radius:24px; padding:28px; display:flex; justify-content:space-between; gap:24px; align-items:center; }
        .searchbar { display:grid; grid-template-columns:1fr 190px 150px auto; gap:10px; margin:22px 0; }
        .searchbar input, .searchbar select { border:1px solid var(--border); border-radius:14px; padding:13px 14px; font-family:inherit; color:var(--navy); }
        .btn { border:0; border-radius:14px; padding:13px 18px; background:var(--accent); color:#fff; font-weight:800; cursor:pointer; text-decoration:none; display:inline-flex; justify-content:center; align-items:center; }
        .cat-row { display:flex; gap:10px; overflow:auto; padding-bottom:8px; margin-bottom:20px; }
        .cat-chip { flex-shrink:0; background:#fff; border:1px solid var(--border); color:var(--navy2); text-decoration:none; border-radius:999px; padding:10px 15px; font-size:13px; font-weight:800; }
        .cat-chip.on { background:var(--navy); color:#fff; }
        .prod-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:18px; }
        .pcard { background:#fff; border:1px solid var(--border); border-radius:18px; overflow:hidden; display:flex; flex-direction:column; min-height:360px; }
        .pimg { height:170px; background:var(--card); display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .pimg img { width:100%; height:100%; object-fit:cover; }
        .pbody { padding:16px; display:flex; flex-direction:column; gap:8px; flex:1; }
        .badge { width:max-content; background:#EEF4FF; color:var(--accent); border-radius:999px; padding:4px 9px; font-size:10px; font-weight:800; }
        .pname { font-size:15px; font-weight:800; color:var(--navy); line-height:1.35; min-height:42px; }
        .muted { color:var(--muted); font-size:12px; }
        .price { margin-top:auto; font-size:19px; color:var(--accent); font-weight:900; }
        .cart-btn { width:100%; border:0; border-radius:12px; background:var(--navy); color:#fff; padding:11px; font-weight:800; cursor:pointer; }
    </style>
@endsection

@section('content')
    <div class="shop-wrap">
        <div class="shop-hero">
            <div>
                <div style="font-size:11px; font-weight:900; letter-spacing:1px; text-transform:uppercase; color:#9cc7ff;">Distri Mart</div>
                <h1 style="font-family:'Fraunces',serif; font-size:34px; margin-top:5px;">Belanja kebutuhan minimarket</h1>
                <p style="color:rgba(255,255,255,.72); margin-top:8px; font-size:14px;">Produk dummy API dan stok lokal, checkout tetap dilindungi validasi nota VERIDITY.</p>
            </div>
            <a href="{{ route('distri.cart') }}" class="btn" style="background:#fff; color:var(--navy);">Keranjang</a>
        </div>

        @if (session('success'))
            <div style="background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); border-radius:14px; padding:14px; margin-top:18px; font-weight:800;">{{ session('success') }}</div>
        @endif

        <form class="searchbar" method="GET" action="{{ route('distri.catalog') }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk, brand, atau kebutuhan harian...">
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="sort">
                <option value="">Terbaru</option>
                <option value="price_low" @selected(request('sort') === 'price_low')>Harga rendah</option>
                <option value="price_high" @selected(request('sort') === 'price_high')>Harga tinggi</option>
                <option value="rating" @selected(request('sort') === 'rating')>Rating</option>
            </select>
            <button class="btn" type="submit">Cari</button>
        </form>

        <div class="cat-row">
            <a href="{{ route('distri.catalog') }}" class="cat-chip {{ !request('category') ? 'on' : '' }}">Semua</a>
            @foreach ($categories as $category)
                <a href="{{ route('distri.catalog', ['category' => $category->slug]) }}" class="cat-chip {{ request('category') === $category->slug ? 'on' : '' }}">{{ $category->name }}</a>
            @endforeach
        </div>

        <div style="margin-bottom:16px; font-size:13px; color:var(--muted); font-weight:700;">Menampilkan {{ $products->count() }} produk</div>

        <div class="prod-grid">
            @foreach ($products as $product)
                <div class="pcard">
                    <div class="pimg">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @elseif ($product->image)
                            <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}">
                        @else
                            <span class="muted">No image</span>
                        @endif
                    </div>
                    <div class="pbody">
                        <span class="badge">{{ $product->category_name ?? 'Minimarket' }}</span>
                        <div class="pname">{{ $product->name }}</div>
                        <div class="muted">{{ $product->brand ?? 'Distri' }} · Stok {{ $product->stock ?? 0 }} · Rating {{ number_format($product->rating ?? 0, 1) }}</div>
                        @if (($product->discount_percentage ?? 0) > 0)
                            <div class="muted" style="color:var(--red); font-weight:800;">Diskon {{ number_format($product->discount_percentage, 0) }}%</div>
                        @endif
                        <div class="price">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                        <div style="display:grid; grid-template-columns:1fr auto; gap:8px;">
                            <a href="{{ route('distri.product.show', $product->id) }}" class="cart-btn" style="text-align:center; text-decoration:none;">Detail</a>
                            <form method="POST" action="{{ route('distri.cart.add') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button class="cart-btn" type="submit" title="Tambah ke keranjang">🛒</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
