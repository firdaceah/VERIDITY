<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VeriDity - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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

        #user-menu-toggle:checked~#user-mobile-menu {
            display: flex;
        }

        @media (max-width: 768px) {
            nav .container {
                flex-direction: row;
                align-items: center;
                gap: 1rem;
                flex-wrap: wrap;
            }

            nav .desktop-links {
                display: none;
            }

            #user-mobile-menu {
                width: 100%;
                display: none;
                flex-direction: column;
                gap: .75rem;
                background: rgba(15, 23, 42, .96);
                border: 1px solid rgb(30 41 59);
                border-radius: 1rem;
                padding: 1rem;
            }

            main.container {
                padding-left: 1rem;
                padding-right: 1rem;
                padding-top: 1.5rem;
            }

            .grid {
                grid-template-columns: 1fr !important;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-950 text-white font-sans">
    <nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="/dashboard" class="text-xl font-bold text-blue-500 italic">VeriDity.</a>
            <label for="user-menu-toggle" class="md:hidden cursor-pointer text-blue-400 text-2xl">
                <i class="fa-solid fa-bars"></i>
            </label>
            <input type="checkbox" id="user-menu-toggle" class="hidden">
            <div class="desktop-links flex items-center gap-6">
                <a href="/dashboard" class="text-sm font-medium hover:text-blue-400 transition">Beranda</a>
                <a href="/my-audits" class="text-sm font-medium hover:text-blue-400 transition">Riwayat Saya</a>
                <a href="{{ route('user.profile') }}" class="text-sm font-medium hover:text-blue-400 transition">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        class="text-sm font-bold text-red-500 bg-red-500/10 px-4 py-2 rounded-xl hover:bg-red-500 hover:text-white transition">
                        Keluar
                    </button>
                </form>
            </div>
            <div id="user-mobile-menu" class="md:hidden">
                <a href="/dashboard" class="text-sm font-medium hover:text-blue-400 transition">Beranda</a>
                <a href="/my-audits" class="text-sm font-medium hover:text-blue-400 transition">Riwayat Saya</a>
                <a href="{{ route('user.profile') }}" class="text-sm font-medium hover:text-blue-400 transition">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-left text-sm font-bold text-red-400 bg-red-500/10 px-4 py-2 rounded-xl">
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
