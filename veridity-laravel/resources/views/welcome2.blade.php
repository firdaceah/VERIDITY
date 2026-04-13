@extends('layouts.app')

@section('title', 'Forensic Toolkit')

@section('content')
    <header class="container mx-auto px-6 pt-16 md:pt-28 pb-20 md:pb-32 text-center">
        <h1 class="text-4xl md:text-7xl font-extrabold mb-6 leading-[1.1] tracking-tight">
            Detect <span class="text-blue-500 italic">Image Manipulation</span> <br class="hidden md:block"> with
            Precision.
        </h1>
        <p class="text-slate-400 text-base md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
            Toolkit forensik digital berbasis web untuk menganalisis keaslian gambar menggunakan metode ELA, Noise & Ghost,
            Metadata
            Analysis, dan AI-Generated Detection.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('register') }}"
                class="bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-xl font-bold text-lg transition flex items-center justify-center shadow-xl shadow-blue-600/20">
                Mulai Analisis Gratis <i class="fa-solid fa-arrow-right ml-2"></i>
            </a>
            <button onclick="openDemo()"
                class="border border-slate-700 hover:bg-slate-800 px-8 py-4 rounded-xl font-bold text-lg transition">
                Lihat Demo
            </button>
        </div>
    </header>

    <section class="py-20 bg-slate-800/20 border-t border-slate-800">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-2xl md:text-4xl font-bold mb-4 text-blue-400 leading-snug">Satu Fitur, Berbagai Solusi</h2>
            <p class="text-slate-400 text-sm md:text-base max-w-3xl mx-auto mb-12">
                Platform ini secara cerdas mampu memproses berbagai kategori gambar tanpa perlu konfigurasi manual
                melalui 4 metode analisis simultan:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    class="p-8 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all group">
                    <div
                        class="w-16 h-16 mx-auto bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-500/20 transition-colors">
                        <i
                            class="fa-solid fa-user-shield text-3xl text-blue-500 group-hover:scale-110 transition-transform"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-2">Foto Manusia</h4>
                    <p class="text-slate-400 text-sm">Verifikasi keaslian foto profil identitas wajah, dan deteksi rekayasa
                        pada atribut fisik.</p>
                </div>
                <div
                    class="p-8 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all group">
                    <div
                        class="w-16 h-16 mx-auto bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-500/20 transition-colors">
                        <i
                            class="fa-solid fa-file-invoice text-3xl text-blue-500 group-hover:scale-110 transition-transform"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-2">Dokumen & Teks</h4>
                    <p class="text-slate-400 text-sm">Analisis integritas dokumen scan, bukti transfer, atau ID card untuk
                        mendeteksi editing teks.</p>
                </div>
                <div
                    class="p-8 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all group sm:col-span-2 lg:col-span-1">
                    <div
                        class="w-16 h-16 mx-auto bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-500/20 transition-colors">
                        <i
                            class="fa-solid fa-boxes-stacked text-3xl text-blue-500 group-hover:scale-110 transition-transform"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-2">Objek Umum</h4>
                    <p class="text-slate-400 text-sm">Deteksi manipulasi pada foto pemandangan, barang bukti, atau objek
                        lingkungan lainnya.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-900 py-20 border-y border-slate-800">
        <div class="container mx-auto px-6">
            <h3 class="text-center text-xl md:text-2xl font-bold mb-16 opacity-50 uppercase tracking-[0.2em]">Our Core
                Methods</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    class="p-6 rounded-2xl bg-slate-800/30 border border-slate-700 hover:border-blue-500 transition duration-300">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-layer-group text-blue-500 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-3">Error Level Analysis</h3>
                    <p class="text-slate-400 text-xs leading-relaxed italic">Mendeteksi perbedaan level kompresi JPEG untuk
                        menemukan area yang dimodifikasi.</p>
                </div>

                <div
                    class="p-6 rounded-2xl bg-slate-800/30 border border-slate-700 hover:border-orange-500 transition duration-300">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-ghost text-orange-500 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-3">Noise & Ghost Analysis</h3>
                    <p class="text-slate-400 text-xs leading-relaxed italic">Mengidentifikasi anomali noise dan jejak
                        "ghosting" akibat proses copy-move atau cloning.</p>
                </div>

                <div
                    class="p-6 rounded-2xl bg-slate-800/30 border border-slate-700 hover:border-purple-500 transition duration-300">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-circle-info text-purple-500 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-3">Metadata Deep Dive</h3>
                    <p class="text-slate-400 text-xs leading-relaxed italic">Ekstraksi EXIF data, software editing yang
                        digunakan, hingga riwayat modifikasi file.</p>
                </div>

                <div
                    class="p-6 rounded-2xl bg-slate-800/30 border border-slate-700 hover:border-emerald-500 transition duration-300">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mb-6">
                        <i class="fa-solid fa-robot text-emerald-500 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-3">AI Detection</h3>
                    <p class="text-slate-400 text-xs leading-relaxed italic">Menganalisis pola pixel non-natural untuk
                        memvalidasi apakah gambar di-generate oleh AI/GAN.</p>
                </div>
            </div>
        </div>
    </section>

    <div id="demoModal"
        class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
        <div class="bg-slate-900 border border-slate-800 w-full max-w-4xl rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                <h3 class="text-xl font-bold text-blue-500 italic">VeriDity Interactive Demo</h3>
                <button onclick="closeDemo()" class="text-slate-400 hover:text-white transition text-2xl">&times;</button>
            </div>
            <div class="aspect-video bg-black flex items-center justify-center">
                <p class="text-slate-500 italic text-sm text-center px-10">
                    <i class="fa-solid fa-play-circle text-5xl mb-4 block"></i>
                    Video demo sedang disiapkan. Menampilkan simulasi analisis 4-layer pada foto dokumen dan wajah.
                </p>
            </div>
        </div>
    </div>

    <script>
        function openDemo() {
            document.getElementById('demoModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDemo() {
            document.getElementById('demoModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
@endsection
