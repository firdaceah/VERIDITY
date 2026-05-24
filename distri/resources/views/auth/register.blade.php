@extends('layouts.app')
@section('title', 'Daftar Akun Reseller Grosir')

@section('content')
    <div class="auth-container">
        <h2 style="font-family: 'Fraunces', serif; font-size: 26px; margin-bottom: 8px; text-align: center;">Join
            distri<span>.</span></h2>
        <p style="font-size: 13px; color: var(--muted); text-align: center; margin-bottom: 24px;">Daftarkan akun keagenan
            grosir Anda sekarang.</p>

        {{-- Tampilkan Error Validasi jika ada --}}
        @if ($errors->any())
            <div
                style="background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); padding: 12px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nama Lengkap Perusahaan / Toko</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Toko Berkah Jaya"
                    value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Alamat Email Resmi</label>
                <input type="email" name="email" class="form-control" placeholder="nama@toko.com"
                    value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label>Password Akun</label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password Anda"
                    required>
            </div>
            <button type="submit" class="form-control"
                style="background: var(--accent); color: #fff; font-weight: 700; border: none; cursor: pointer; margin-top: 8px; border-radius: 12px; padding: 14px;">Daftar
                Keagenan</button>
        </form>

        <p style="font-size: 13px; text-align: center; margin-top: 20px; color: var(--muted);">
            Sudah memiliki akun grosir? <a href="{{ route('login') }}"
                style="color: var(--accent); font-weight: 600; text-decoration: none;">Masuk di sini</a>
        </p>
    </div>
@endsection
