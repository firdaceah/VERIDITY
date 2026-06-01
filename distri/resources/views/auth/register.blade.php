@extends('layouts.app')
@section('title', 'Daftar Akun Reseller Grosir')

@section('content')
    <div class="auth-container">
        <h2 style="font-family:'Fraunces',serif; font-size:26px; margin-bottom:8px; text-align:center;">Join distri<span>.</span></h2>
        <p style="font-size:13px; color:var(--muted); text-align:center; margin-bottom:24px;">Daftarkan akun keagenan grosir Anda sekarang.</p>

        @if ($errors->any())
            <div style="background:var(--red-bg); color:var(--red); border:1px solid var(--red-border); padding:12px; border-radius:10px; margin-bottom:16px; font-size:13px;">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nama Lengkap Perusahaan / Toko</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Toko Berkah Jaya" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Alamat Email Resmi</label>
                <input type="email" name="email" class="form-control" placeholder="nama@toko.com" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label>Password Akun</label>
                <div class="password-wrap">
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    <button type="button" class="toggle-password" aria-label="Tampilkan password" onclick="togglePassword(this)">
                        <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="m2 2 20 20"/><path d="M6.7 6.7C3.8 8.7 2 12 2 12s3 7 10 7c1.9 0 3.5-.5 4.8-1.2"/><path d="M9.9 4.3c.7-.2 1.4-.3 2.1-.3 7 0 10 8 10 8s-.6 1.4-1.8 2.8"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div class="password-wrap">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password Anda" required>
                    <button type="button" class="toggle-password" aria-label="Tampilkan password" onclick="togglePassword(this)">
                        <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="m2 2 20 20"/><path d="M6.7 6.7C3.8 8.7 2 12 2 12s3 7 10 7c1.9 0 3.5-.5 4.8-1.2"/><path d="M9.9 4.3c.7-.2 1.4-.3 2.1-.3 7 0 10 8 10 8s-.6 1.4-1.8 2.8"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="form-control" style="background:var(--accent); color:#fff; font-weight:700; border:none; cursor:pointer; margin-top:8px; border-radius:12px; padding:14px;">Daftar Keagenan</button>
        </form>

        <p style="font-size:13px; text-align:center; margin-top:20px; color:var(--muted);">
            Sudah memiliki akun grosir? <a href="{{ route('login') }}" style="color:var(--accent); font-weight:600; text-decoration:none;">Masuk di sini</a>
        </p>
    </div>

    <script>
        function togglePassword(button) {
            const input = button.closest('.password-wrap').querySelector('input');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('.eye-open').style.display = visible ? 'block' : 'none';
            button.querySelector('.eye-closed').style.display = visible ? 'none' : 'block';
        }
    </script>
@endsection
