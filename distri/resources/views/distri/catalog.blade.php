@extends('layouts.app')
@section('title', 'Katalog Produk Grosir')

@section('styles')
    <style>
        .cat-layout {
            display: flex;
            min-height: calc(100vh - 60px);
        }

        .cat-sidebar {
            width: 240px;
            background: var(--white);
            border-right: 1px solid var(--border);
            padding: 32px 20px;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .cat-item {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 2px;
            text-decoration: none;
            display: block;
        }

        .cat-item.on {
            background: #EEF4FF;
            color: var(--navy2);
        }

        .cat-main {
            flex: 1;
            padding: 36px 48px;
        }

        .prod-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .pcard {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .pimg-container {
            height: 160px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid var(--border);
        }

        .pimg-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pbody {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .pname {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 4px;
        }

        .punit {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .pprice {
            font-size: 18px;
            font-weight: 800;
            color: var(--accent);
            margin-top: auto;
        }

        .pbtn {
            width: 100%;
            margin-top: 14px;
            padding: 12px;
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .pbtn:hover {
            background: var(--navy2);
        }
    </style>
@endsection

@section('content')
    <div class="cat-layout">
        <div class="cat-sidebar">
            <div class="sidebar-label">Kategori Produk</div>
            <a href="#" class="cat-item on">Semua Produk</a>
        </div>
        <div class="cat-main">
            <div style="margin-bottom: 28px;">
                <h2 style="font-family: 'Fraunces', serif; font-size: 28px;">Katalog Produk Utama</h2>
                <p style="font-size: 13px; color: var(--muted);">Menampilkan {{ $products->count() }} produk grosir rill dari Oracle.</p>
            </div>
            <div class="prod-grid">
                @foreach ($products as $product)
                    <div class="pcard">
                        <div class="pimg-container">
                            @if ($product->image)
                                {{-- Diubah ke folder public terluar 'products/' mendampingi perubahan controller kemarin --}}
                                <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}">
                            @else
                                <span style="font-size: 48px;">📦</span>
                            @endif
                        </div>
                        <div class="pbody">
                            <div class="pname">{{ $product->name }}</div>
                            <div class="punit">Minimal Pemesanan: {{ $product->min_qty }} {{ $product->unit }}</div>
                            <div class="pprice">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                            <a href="{{ route('distri.checkout', $product->id) }}" class="pbtn">Pesan Sekarang</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection