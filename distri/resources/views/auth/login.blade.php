@extends('layouts.app')
@section('title', 'Login Reseller')

@section('content')
    <div class="auth-container">
        <h2 style="font-family: 'Fraunces', serif; font-size: 26px; margin-bottom: 8px; text-align: center;">
            distri<span>.</span> login</h2>
        <p style="font-size: 13px; color: var(--muted); text-align: center; margin-bottom: 24px;">Selamat datang kembali,
            silakan masuk ke dashboard agen.</p>

        {{-- Notifikasi Sukses setelah Register --}}
        @if (session('success'))
            <div
                style="background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); padding: 12px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; font-weight: 600;">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tampilkan Error Login --}}
        @if ($errors->any())
            <div
                style="background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); padding: 12px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Email Terdaftar</label>
                <input type="email" name="email" class="form-control" placeholder="masukkan email toko Anda"
                    value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="form-control"
                style="background: var(--accent); color: #fff; font-weight: 700; border: none; cursor: pointer; margin-top: 8px; border-radius: 12px; padding: 14px;">Masuk
                Dashboard</button>
        </form>

        <p style="font-size: 13px; text-align: center; margin-top: 20px; color: var(--muted);">
            Belum tergabung sebagai reseller? <a href="{{ route('register') }}"
                style="color: var(--accent); font-weight: 600; text-decoration: none;">Daftar Baru</a>
        </p>
    </div>
@endsection
