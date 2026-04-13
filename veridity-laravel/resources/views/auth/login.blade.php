<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VeriDity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; width: 100%; margin: auto; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .invalid-feedback { font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary">Veri<span class="text-dark">Dity</span></h3>
            <p class="text-muted">Masuk untuk memulai verifikasi</p>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                       placeholder="user@student.pens.ac.id" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                       placeholder="••••••••" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- <div class="mb-3 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label small text-muted" for="remember">Ingat Saya</label>
            </div> --}}

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login</button>
        </form>

        <div class="text-center mt-3">
            <p class="small text-muted">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
            <a href="/" class="small text-decoration-none">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>