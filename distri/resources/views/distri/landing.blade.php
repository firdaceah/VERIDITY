@extends('layouts.app')
@section('title', 'Distribusi Aman, Transaksi Terjaga')

@section('styles')
    <style>
        .hero {
            background: var(--navy);
            padding: 80px;
            display: flex;
            align-items: center;
            gap: 80px;
        }

        .hero-left {
            flex: 1;
        }

        .hero-chip {
            display: inline-block;
            background: rgba(75, 155, 255, 0.18);
            border: 1px solid rgba(75, 155, 255, 0.35);
            color: #7BB8FF;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 24px;
        }

        .hero h1 {
            font-family: 'Fraunces', serif;
            font-size: 52px;
            font-weight: 700;
            color: #fff;
            line-height: 1.12;
            margin-bottom: 20px;
        }

        .hero h1 em {
            color: var(--accent2);
            font-style: italic;
        }

        .hero-sub {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.72;
            margin-bottom: 36px;
            max-width: 520px;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            padding: 14px 32px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: .15s;
        }

        .btn-primary:hover {
            background: var(--accent2);
        }

        .hero-stats {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 260px;
        }

        .hstat {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px 24px;
        }

        .hstat-n {
            font-size: 32px;
            font-weight: 800;
            color: var(--accent2);
        }

        .hstat-l {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 4px;
        }
    </style>
@endsection

@section('content')
    <div class="hero">
        <div class="hero-left">
            <div class="hero-chip">✦ Veridity Engine — AI-Powered Protection</div>
            <h1>Distribusi Aman,<br><em>Transaksi Terjaga.</em></h1>
            <p class="hero-sub">Platform distribusi grosir dengan validasi nota transfer berbasis kecerdasan buatan. Setiap
                pembayaran dikirim dan diperiksa otomatis oleh Veridity Engine.</p>
            <div class="hero-btns">
                <a href="{{ route('distri.catalog') }}" class="btn-primary">Mulai Pesan Sekarang →</a>
            </div>
        </div>
        <div class="hero-stats">
            <div class="hstat">
                <div class="hstat-n">2.400+</div>
                <div class="hstat-l">Reseller Aktif Terdaftar</div>
            </div>
            <div class="hstat">
                <div class="hstat-n">99.8%</div>
                <div class="hstat-l">Nota Berhasil Terverifikasi</div>
            </div>
        </div>
    </div>
@endsection
