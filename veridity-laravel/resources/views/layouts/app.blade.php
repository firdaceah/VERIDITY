<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>VeriDity - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        #menu-toggle:checked~#mobile-menu {
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
            }

            h1 {
                font-size: 2.5rem !important;
                line-height: 1.1 !important;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>

<body class="bg-[#111028] text-white font-sans overflow-x-hidden">
    <nav
        class="flex items-center justify-between px-6 md:px-10 py-6 bg-[#0E0E20]/80 backdrop-blur-md sticky top-0 z-50 border-b border-white/10">
        <div class="text-xl md:text-2xl font-bold tracking-tighter text-[#39D2DD]">
            <i class="fa-solid fa-shield-halved"></i> VeriDity.
        </div>

        <div class="hidden md:flex items-center space-x-8">
            @auth
                <a href="{{ url('/dashboard') }}" class="hover:text-[#39D2DD] transition text-sm">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-[#39D2DD] transition text-sm font-medium">Login</a>
                <a href="{{ route('register') }}"
                    class="bg-[#4338CA] hover:bg-[#372FA8] px-5 py-2 rounded-full text-sm font-bold transition shadow-lg shadow-[#4338CA]/20">
                    Get Started
                </a>
            @endauth
        </div>

        <label for="menu-toggle" class="md:hidden cursor-pointer text-[#39D2DD] text-2xl">
            <i class="fa-solid fa-bars"></i>
        </label>
        <input type="checkbox" id="menu-toggle" class="hidden">

        <div id="mobile-menu"
            class="hidden absolute top-full left-0 w-full bg-[#0E0E20] border-b border-white/10 p-6 md:hidden transition-all animate-fade-in">
            <div class="flex flex-col space-y-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-lg border-b border-white/10 pb-2">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-lg border-b border-white/10 pb-2">Login</a>
                    <a href="{{ route('register') }}" class="bg-[#4338CA] text-center py-3 rounded-xl font-bold">Get
                        Started</a>
                @endauth
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-[#111028] border-t border-white/10 pt-24 pb-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-20">

                <div class="space-y-8 text-left">
                    <div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-4">Butuh Bantuan <span class="text-[#39D2DD] italic block">Analisis Forensik?</span></h2>
                        <p class="text-slate-400 leading-relaxed max-w-md">
                            Punya pertanyaan mengenai metode ELA atau ingin berkolaborasi? Hubungi kami melalui saluran
                            resmi di bawah ini.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4 group">
                            <div
                                class="w-12 h-12 bg-[#0E0E20] rounded-2xl flex items-center justify-center border border-white/10 group-hover:border-[#39D2DD] transition">
                                <i class="fa-solid fa-envelope text-[#39D2DD]"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-[0.2em] font-bold">Email Support
                                </p>
                                <p class="text-slate-200 text-sm">veridity@admin.com</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 group">
                            <div
                                class="w-12 h-12 bg-[#0E0E20] rounded-2xl flex items-center justify-center border border-white/10 group-hover:border-[#39D2DD] transition">
                                <i class="fa-solid fa-location-dot text-[#39D2DD]"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-[0.2em] font-bold">Location</p>
                                <p class="text-slate-200 text-sm">Kampus PENS, Surabaya, Indonesia</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <a href="#"
                            class="w-10 h-10 rounded-xl bg-[#0E0E20] border border-white/10 flex items-center justify-center hover:bg-[#4338CA] transition duration-300">
                            <i class="fa-brands fa-github text-white"></i>
                        </a>
                        <a href="#"
                            class="w-10 h-10 rounded-xl bg-[#0E0E20] border border-white/10 flex items-center justify-center hover:bg-[#4338CA] transition duration-300">
                            <i class="fa-brands fa-linkedin"></i>
                        </a>
                        <a href="#"
                            class="w-10 h-10 rounded-xl bg-[#0E0E20] border border-white/10 flex items-center justify-center hover:bg-[#4338CA] transition duration-300">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <div class="bg-[#0E0E20]/50 backdrop-blur-sm p-8 rounded-3xl border border-white/10">
                    <form action="#" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" placeholder="Nama"
                                class="w-full px-5 py-3 bg-[#111028] border border-white/10 rounded-xl focus:ring-1 focus:ring-[#39D2DD] outline-none text-sm">
                            <input type="email" placeholder="Email"
                                class="w-full px-5 py-3 bg-[#111028] border border-white/10 rounded-xl focus:ring-1 focus:ring-[#39D2DD] outline-none text-sm">
                        </div>
                        <input type="text" placeholder="Subjek"
                            class="w-full px-5 py-3 bg-[#111028] border border-white/10 rounded-xl focus:ring-1 focus:ring-[#39D2DD] outline-none text-sm">
                        <textarea placeholder="Pesan..." rows="3"
                            class="w-full px-5 py-3 bg-[#111028] border border-white/10 rounded-xl focus:ring-1 focus:ring-[#39D2DD] outline-none text-sm"></textarea>
                        <button type="submit"
                            class="w-full bg-[#4338CA] hover:bg-[#372FA8] py-4 rounded-xl font-bold transition">
                            Kirim Pesan <i class="fa-solid fa-paper-plane ml-2 text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div
                class="pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-slate-500 text-xs">
                <div class="flex flex-col md:flex-row items-center gap-2 md:gap-6">
                    <span class="font-bold text-white italic">VeriDity Smart Detection</span>
                    <span>Teknik Informatika - PENS</span>
                </div>
                <div class="text-center md:text-right">
                    <p>&copy; 2026 Developed by <span class="text-[#39D2DD] font-bold">Team 3</span>. All Rights Reserved.
                    </p>
                    <p class="opacity-50 mt-1">Multi-Method Digital Forensics & Image Analysis</p>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>
