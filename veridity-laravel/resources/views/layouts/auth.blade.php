<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>VeriDity - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-slate-900 text-white font-sans min-h-screen flex items-center justify-center px-6 py-12">

    <div class="fixed top-6 left-6 z-50">
        <a href="/" class="flex items-center gap-2 text-slate-400 hover:text-blue-500 transition-all group">
            <div
                class="w-10 h-10 rounded-full bg-slate-800/50 border border-slate-700 flex items-center justify-center group-hover:border-blue-500/50 shadow-lg">
                <i class="fa-solid fa-arrow-left"></i>
            </div>
            <span class="text-sm font-medium hidden md:block">Kembali ke Beranda</span>
        </a>
    </div>

    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <a href="/" class="text-3xl font-bold tracking-tighter text-blue-500">
                <i class="fa-solid fa-shield-halved"></i> VeriDity.
            </a>

            <h2 class="text-xl font-semibold mt-2 text-slate-200">
                @if (Route::is('login'))
                    Selamat Datang Kembali
                @elseif(Route::is('register'))
                    Buat Akun Baru
                @else
                    Autentikasi Sistem
                @endif
            </h2>

            <p class="text-slate-400 text-sm mt-1">
                @if (Route::is('login'))
                    Masuk untuk memulai analisis forensik
                @else
                    Daftar untuk akses fitur lengkap toolkit
                @endif
            </p>
        </div>

        <main>
            @yield('auth-form')
        </main>

        <p class="text-center mt-8 text-slate-500 text-xs uppercase tracking-widest">
            Informatics Engineering &copy; 2026
        </p>
    </div>
</body>

</html>
