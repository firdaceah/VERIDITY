@extends('layouts.app')
@section('title', $product->name)

@section('styles')
    <style>
        .detail-wrap { max-width:1180px; margin:36px auto; padding:0 24px 48px; }
        .product-shell { display:grid; grid-template-columns:460px 1fr; gap:28px; background:#fff; border:1px solid var(--border); border-radius:24px; padding:28px; }
        .hero-img { height:430px; background:var(--card); border-radius:20px; overflow:hidden; display:flex; align-items:center; justify-content:center; }
        .hero-img img { width:100%; height:100%; object-fit:cover; }
        .badge { display:inline-flex; background:#EEF4FF; color:var(--accent); padding:7px 12px; border-radius:999px; font-size:11px; font-weight:900; }
        .btn-row { display:flex; gap:12px; margin-top:22px; }
        .btn { border:0; border-radius:14px; padding:14px 18px; font-weight:900; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
        .btn-cart { width:54px; background:var(--navy); color:#fff; font-size:20px; }
        .btn-buy { flex:1; background:var(--accent); color:#fff; }
        .related { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px; margin-top:18px; }
        .rel-card { background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden; text-decoration:none; color:var(--navy); }
    </style>
@endsection

@section('content')
    <div class="detail-wrap">
        <a href="{{ route('distri.catalog') }}" style="text-decoration:none; color:var(--accent); font-size:13px; font-weight:900;">&larr; Kembali ke katalog</a>

        <div class="product-shell" style="margin-top:16px;">
            <div class="hero-img">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                @elseif ($product->image)
                    <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}">
                @else
                    <span style="color:var(--muted);">No image</span>
                @endif
            </div>
            <div>
                <span class="badge">{{ $product->category_name ?? 'Minimarket' }}</span>
                <h1 style="font-family:'Fraunces',serif; font-size:42px; line-height:1.08; margin-top:14px;">{{ $product->name }}</h1>
                <p style="font-size:14px; color:var(--muted); margin-top:10px;">{{ $product->brand ?? 'Distri Mart' }} · Stok {{ $product->stock ?? 0 }} · Rating {{ number_format($product->rating ?? 0, 1) }}</p>
                <div style="font-size:32px; color:var(--accent); font-weight:900; margin-top:18px;">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                @if (($product->discount_percentage ?? 0) > 0)
                    <div style="margin-top:8px; color:var(--red); font-weight:900;">Diskon {{ number_format($product->discount_percentage, 0) }}%</div>
                @endif
                <p style="line-height:1.8; color:var(--navy2); margin-top:18px;">{{ $product->description ?? 'Produk minimarket untuk kebutuhan reseller dan stok toko harian.' }}</p>

                <div class="btn-row">
                    <form method="POST" action="{{ route('distri.cart.add') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button class="btn btn-cart" type="submit" title="Tambah ke keranjang">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.96a2 2 0 0 0 1.95-1.57l1.35-6.43H5.12"/></svg>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('distri.cart.add') }}" style="flex:1;">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button class="btn btn-buy" type="submit" name="redirect_checkout" value="1">Langsung Checkout</button>
                    </form>
                </div>
            </div>
        </div>

        <div style="background:#fff; border:1px solid var(--border); border-radius:22px; padding:24px; margin-top:22px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:18px;">
                <div>
                    <h2 style="font-family:'Fraunces',serif; font-size:28px;">Rating dan Ulasan</h2>
                    <p style="font-size:13px; color:var(--muted); margin-top:4px;">Ringkasan penilaian pembeli untuk produk ini.</p>
                </div>
                <div style="font-size:36px; font-weight:900; color:var(--accent);">{{ number_format($product->rating ?? 0, 1) }}</div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:18px;">
                <div style="background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px;"><strong>Kualitas produk</strong><p style="font-size:12px; color:var(--muted); margin-top:6px;">Kemasan dan stok dinilai konsisten.</p></div>
                <div style="background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px;"><strong>Harga reseller</strong><p style="font-size:12px; color:var(--muted); margin-top:6px;">Cocok untuk pembelian stok toko.</p></div>
                <div style="background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px;"><strong>Pengiriman</strong><p style="font-size:12px; color:var(--muted); margin-top:6px;">Diproses setelah pembayaran tervalidasi.</p></div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <h2 style="font-family:'Fraunces',serif; margin-top:30px;">Produk terkait</h2>
            <div class="related">
                @foreach ($related as $item)
                    <a class="rel-card" href="{{ route('distri.product.show', $item->id) }}">
                        <div style="height:120px; background:var(--card);">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" style="width:100%; height:100%; object-fit:cover;" alt="{{ $item->name }}">
                            @endif
                        </div>
                        <div style="padding:12px;">
                            <div style="font-weight:900;">{{ $item->name }}</div>
                            <div style="color:var(--accent); font-weight:900; margin-top:6px;">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
