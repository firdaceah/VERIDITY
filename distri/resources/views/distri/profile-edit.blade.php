@extends('layouts.app')
@section('title', 'Edit Profile')

@section('styles')
    <style>
        .edit-wrap { max-width: 720px; margin: 40px auto; padding: 0 24px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:20px; padding:26px; }
        .btn { border:0; border-radius:12px; padding:12px 16px; background:var(--accent); color:#fff; font-weight:900; cursor:pointer; text-decoration:none; }
    </style>
@endsection

@section('content')
    <div class="edit-wrap">
        <a href="{{ route('distri.profile') }}" style="text-decoration:none; color:var(--accent); font-size:13px; font-weight:900;">&larr; Kembali ke profile</a>

        <div class="card" style="margin-top:16px;">
            <h2 style="font-family:'Fraunces',serif; font-size:30px;">Edit Data Profile</h2>
            <p style="font-size:13px; color:var(--muted); margin-top:4px;">Ubah nama, email, atau password akun.</p>

            @if ($errors->any())
                <div style="background:var(--red-bg); border:1px solid var(--red-border); color:var(--red); padding:12px; border-radius:12px; margin-top:18px; font-size:13px; font-weight:800;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('distri.profile.update') }}" style="margin-top:24px;">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nama Toko / Pengguna</label>
                    <input class="form-control" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="password-wrap">
                        <input class="form-control" type="password" name="password" placeholder="Isi jika ingin mengganti password">
                        <button type="button" class="toggle-password" aria-label="Tampilkan password" onclick="togglePassword(this)">
                            <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="m2 2 20 20"/><path d="M6.7 6.7C3.8 8.7 2 12 2 12s3 7 10 7c1.9 0 3.5-.5 4.8-1.2"/><path d="M9.9 4.3c.7-.2 1.4-.3 2.1-.3 7 0 10 8 10 8s-.6 1.4-1.8 2.8"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="password-wrap">
                        <input class="form-control" type="password" name="password_confirmation" placeholder="Ulangi password baru">
                        <button type="button" class="toggle-password" aria-label="Tampilkan password" onclick="togglePassword(this)">
                            <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="m2 2 20 20"/><path d="M6.7 6.7C3.8 8.7 2 12 2 12s3 7 10 7c1.9 0 3.5-.5 4.8-1.2"/><path d="M9.9 4.3c.7-.2 1.4-.3 2.1-.3 7 0 10 8 10 8s-.6 1.4-1.8 2.8"/></svg>
                        </button>
                    </div>
                </div>
                <button class="btn" type="submit">Simpan Perubahan</button>
            </form>
        </div>
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
