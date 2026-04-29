<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VeriDity - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes scan {
            0% {
                top: 0;
            }

            100% {
                top: 100%;
            }
        }

        .scanner-line {
            height: 2px;
            background: #3b82f6;
            position: absolute;
            width: 100%;
            box-shadow: 0 0 15px #3b82f6;
            animation: scan 2s linear infinite;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-950 text-white font-sans">
    <nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="/dashboard" class="text-xl font-bold text-blue-500 italic">VeriDity.</a>
            <div class="flex items-center gap-6">
                <a href="/dashboard" class="text-sm font-medium hover:text-blue-400 transition">Beranda</a>
                <a href="/my-audits" class="text-sm font-medium hover:text-blue-400 transition">Riwayat Saya</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        class="text-sm font-bold text-red-500 bg-red-500/10 px-4 py-2 rounded-xl hover:bg-red-500 hover:text-white transition">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-10">
        @yield('content')
    </main>
</body>

</html>
