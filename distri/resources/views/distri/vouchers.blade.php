@extends('layouts.app')
@section('title', 'Voucher Saya')

@section('styles')
    <style>
        .wrap { max-width:980px; margin:40px auto; padding:0 24px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; margin-top:22px; }
        .voucher { background:#fff; border:1px solid var(--border); border-radius:20px; padding:20px; position:relative; overflow:hidden; }
        .code { display:inline-flex; background:var(--navy); color:#fff; border-radius:999px; padding:8px 12px; font-size:12px; font-weight:900; letter-spacing:1px; }
        .btn { display:inline-flex; margin-top:14px; background:var(--accent); color:#fff; border-radius:12px; padding:10px 14px; text-decoration:none; font-weight:900; font-size:13px; }
    </style>
@endsection

@section('content')
    <div class="wrap">
        <h1 style="font-family:'Fraunces',serif; font-size:34px;">Voucher Saya</h1>
        <p style="font-size:13px; color:var(--muted); margin-top:6px;">Gunakan kode voucher di halaman checkout untuk mendapat potongan pembayaran.</p>
        @if (session('success'))
            <div style="background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); border-radius:12px; padding:12px; margin-top:16px; font-weight:800; font-size:13px;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="background:var(--red-bg); border:1px solid var(--red-border); color:var(--red); border-radius:12px; padding:12px; margin-top:16px; font-weight:800; font-size:13px;">{{ session('error') }}</div>
        @endif

        <div class="grid">
            @foreach ($vouchers as $voucher)
                <div class="voucher">
                    <span class="code">{{ $voucher->code }}</span>
                    <h3 style="font-size:19px; margin-top:14px;">{{ $voucher->name }}</h3>
                    <p style="font-size:13px; color:var(--muted); line-height:1.7; margin-top:8px;">
                        Potongan {{ $voucher->type === 'percent' ? number_format($voucher->value, 0).'%' : 'Rp '.number_format($voucher->value, 0, ',', '.') }} untuk minimal belanja Rp {{ number_format($voucher->minimum_order, 0, ',', '.') }}.
                    </p>
                    <a href="{{ route('distri.vouchers.use', ['code' => $voucher->code, 'checkout' => $checkoutMode, 'product_id' => $checkoutProductId]) }}" class="btn">Pakai Voucher</a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
