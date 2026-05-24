@extends('layouts.user')

@section('title', 'Hasil Analisis')

@section('content')
    <div class="max-w-6xl mx-auto text-slate-100">
        {{-- Navigasi Kembali --}}
        <div class="mb-6">
            <a href="{{ route('user.my-audits') }}" class="text-blue-500 hover:text-blue-400 font-bold text-sm transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Riwayat
            </a>
        </div>

        {{-- Jendela Banner Status Utama (Aman / Warning / Danger) --}}
        <div class="p-6 rounded-3xl mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4
            {{ $analysis->final_result['summary_color'] == 'warning'
                ? 'bg-orange-500/20 border border-orange-500/30'
                : ($analysis->final_result['summary_color'] == 'danger'
                    ? 'bg-red-500/20 border border-red-500/30'
                    : 'bg-emerald-500/20 border border-emerald-500/30') }}">

            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl text-white font-black
                    {{ $analysis->final_result['summary_color'] == 'warning'
                        ? 'bg-orange-500'
                        : ($analysis->final_result['summary_color'] == 'danger'
                            ? 'bg-red-500'
                            : 'bg-emerald-500') }}">
                    <i class="fa-solid {{ $analysis->final_result['summary_color'] == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                </div>
                <div>
                    <h2 class="font-bold text-xl uppercase tracking-tight">
                        Hasil Akhir: {{ $analysis->final_result['summary_label'] ?? 'Unknown' }}
                    </h2>
                    <p class="text-xs opacity-60">Kode Investigasi Dokumen: #VRD-{{ $analysis->id }}</p>
                </div>
            </div>
            <button class="bg-white/10 hover:bg-white/20 text-white px-6 py-2 rounded-xl text-xs font-bold transition duration-200">
                <i class="fa-solid fa-file-pdf mr-1"></i> Unduh Laporan PDF
            </button>
        </div>

        {{-- Layout Utama Viewport dan Metrik Dasar --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Panel Kiri: Ruang Visual Viewport --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900 p-4 rounded-[2.5rem] border border-slate-800">
                    <p class="text-[10px] uppercase font-bold text-slate-500 mb-4 px-4 tracking-widest italic">Visual Analysis Viewport</p>

                    <div class="relative overflow-hidden rounded-2xl bg-slate-950 flex items-center justify-center min-h-[300px]">
                        <img id="mainViewport" src="{{ asset('storage/' . $analysis->s3_path) }}"
                            class="w-full h-auto max-h-[500px] object-contain transition-all duration-500 shadow-2xl" alt="Analyzed Image">
                    </div>

                    {{-- MENU TAB PEMILIHAN VIEWPORT VISUAL (KINI DITAMBAHKAN TAB NOISE) --}}
                    <div class="flex gap-2 mt-4 overflow-x-auto pb-2">
                        <button onclick="switchView('original')" id="btn-original"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-500 rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-blue-600/20">
                            Foto Orisinal
                        </button>
                        <button onclick="switchView('ela')" id="btn-ela"
                            class="px-5 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-bold transition-all duration-150 text-slate-300">
                            Peta Piksel (ELA Map)
                        </button>
                        <button onclick="switchView('noise')" id="btn-noise"
                            class="px-5 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-bold transition-all duration-150 text-slate-300">
                            Kerapatan Residu (Noise Map)
                        </button>
                    </div>
                </div>

                {{-- Mode Detail Analis Forensik (Accordion Toggle untuk Dosen/Peneliti) --}}
                <div class="bg-slate-900 rounded-[2.5rem] border border-slate-800 overflow-hidden">
                    <button onclick="toggleResearchPanel()" class="w-full p-6 flex items-center justify-between text-left hover:bg-slate-800/40 transition duration-200 focus:outline-none">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400">
                                <i class="fa-solid fa-microscope"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-200">Mode Detail Analis Forensik (Peneliti/Dosen)</h4>
                                <p class="text-[11px] text-slate-500">Klik untuk melihat matriks metrik eksponensial mentah dari Python Toolkit</p>
                            </div>
                        </div>
                        <i id="research-chevron" class="fa-solid fa-chevron-down text-slate-400 transition-transform duration-300"></i>
                    </button>

                    <div id="research-panel" class="hidden border-t border-slate-800/60 bg-slate-950 p-6 space-y-6 font-mono text-xs text-slate-300">
                        {{-- Metrik ELA Induk --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-slate-800/60 pb-4">
                            <div>
                                <h5 class="font-bold text-blue-400 mb-2">// STATISTIK PIKSEL KEPADATAN (ELA)</h5>
                                <p>Skor Deviasi Maksimal: <span class="text-slate-400">{{ $analysis->final_result['full_report']['results']['ela']['metrics']['max_diff'] ?? '0' }}</span></p>
                                <p>Rerata Selisih Eror: <span class="text-slate-400">{{ number_format($analysis->final_result['full_report']['results']['ela']['metrics']['mean_diff'] ?? 0, 5) }}</span></p>
                                <p>Nilai ELA Standar Deviasi: <span class="text-slate-400">{{ number_format($analysis->final_result['full_report']['results']['ela']['metrics']['std_diff'] ?? 0, 5) }}</span></p>
                            </div>
                            <div>
                                <h5 class="font-bold text-blue-400 mb-2">// AMBANG GRID MATRIKS BLOK (DCT)</h5>
                                <p>Rerata Std Deviasi Blok: <span class="text-slate-400">{{ number_format($analysis->final_result['full_report']['results']['ela']['block_stats']['block_std_mean'] ?? 0, 5) }}</span></p>
                                <p>Maksimal Std Deviasi Blok: <span class="text-slate-400">{{ number_format($analysis->final_result['full_report']['results']['ela']['block_stats']['block_std_max'] ?? 0, 5) }}</span></p>
                                <p>Skor Total Anomali: <span class="{{ $analysis->ela_score > 15 ? 'text-red-400' : 'text-emerald-400' }} font-bold">{{ number_format($analysis->ela_score, 4) }}</span></p>
                            </div>
                        </div>

                        {{-- Metrik Spektral AI/GAN --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-slate-800/60 pb-4">
                            <div>
                                <h5 class="font-bold text-purple-400 mb-2">// PEMINDAIAN SPEKTRAL REKAYASA AI (GAN)</h5>
                                <p>Varians Frekuensi Radial: <span class="text-slate-400">{{ number_format($analysis->final_result['full_report']['results']['ai_detection']['metrics']['radial_frequency_variance'] ?? 0, 5) }}</span></p>
                                <p>Simetri Kuadran Citra: <span class="text-slate-400">{{ number_format($analysis->final_result['full_report']['results']['ai_detection']['metrics']['quadrant_symmetry'] ?? 0, 5) }}</span></p>
                                <p>Puncak Spektral Terdeteksi: <span class="text-slate-400">{{ $analysis->final_result['full_report']['results']['ai_detection']['metrics']['spectral_peaks_detected'] ?? '0' }} titik</span></p>
                            </div>
                            <div>
                                <h5 class="font-bold text-purple-400 mb-2">// CATATAN LABORATORIUM PROBABILITAS</h5>
                                <p class="text-slate-400 italic">"{{ $analysis->final_result['full_report']['results']['ai_detection']['researcher_note'] ?? '-' }}"</p>
                                <p class="mt-1">Skor Keaslian AI Terpadu: <span class="{{ $analysis->is_deepfake ? 'text-red-400' : 'text-emerald-400' }} font-bold">{{ 100 - (($analysis->final_result['full_report']['results']['ai_detection']['metrics']['gan_score'] ?? 0) * 100) }}%</span></p>
                            </div>
                        </div>

                        {{-- Metrik Residu Kebisingan Kamera --}}
                        <div>
                            <h5 class="font-bold text-amber-400 mb-2">// DATA SENSOR HIGH-PASS NOISE VARIANCE</h5>
                            <p class="text-slate-400 mb-2"><em>"{{ $analysis->final_result['full_report']['results']['noise']['researcher_note'] ?? '-' }}"</em></p>
                            <div class="grid grid-cols-3 gap-2 bg-slate-900 p-2 rounded-xl text-center">
                                <div><p class="text-red-400">Red Channel</p><p class="font-bold text-slate-300">{{ $analysis->final_result['full_report']['results']['noise']['metrics']['channel_noise_variance']['red'] ?? '0' }}</p></div>
                                <div><p class="text-emerald-400">Green Channel</p><p class="font-bold text-slate-300">{{ $analysis->final_result['full_report']['results']['noise']['metrics']['channel_noise_variance']['green'] ?? '0' }}</p></div>
                                <div><p class="text-blue-400">Blue Channel</p><p class="font-bold text-slate-300">{{ $analysis->final_result['full_report']['results']['noise']['metrics']['channel_noise_variance']['blue'] ?? '0' }}</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Panel Kanan: Rangkuman Metrik Dasar untuk User Awam --}}
            <div class="space-y-6">
                <div class="bg-slate-900 p-6 sm:p-8 rounded-[2.5rem] border border-slate-800 shadow-xl">
                    <h4 class="font-bold mb-6 italic text-blue-500 text-sm tracking-wider uppercase">Forensic Metrics (Rangkuman)</h4>
                    
                    <div class="space-y-6">
                        {{-- ELA Slider --}}
                        <div>
                            <div class="flex justify-between text-[10px] mb-2 font-bold uppercase tracking-wide">
                                <span class="text-slate-400">Error Level (ELA Score)</span>
                                <span class="{{ $analysis->ela_score > 15 ? 'text-red-400' : 'text-emerald-400' }} font-mono text-xs">
                                    {{ number_format($analysis->ela_score, 2) }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-800 h-2 rounded-full">
                                <div class="h-2 rounded-full transition-all duration-1000 
                                    {{ $analysis->ela_score > 15 ? 'bg-red-500' : 'bg-emerald-500' }}"
                                    style="width: {{ min(100, max(5, $analysis->ela_score)) }}%"></div>
                            </div>
                        </div>

                        {{-- Lapisan 1: Metadata HP/Kamera --}}
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800/80">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1 tracking-wider">Pemeriksaan Identitas File</p>
                            <p class="text-xs font-bold {{ $analysis->final_result['summary_color'] == 'success' ? 'text-emerald-400' : 'text-orange-400' }}">
                                <i class="fa-solid {{ $analysis->final_result['summary_color'] == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }} mr-1"></i>
                                {{ $analysis->metadata_details['summary']['status'] ?? 'Riwayat Kosong' }}
                            </p>

                            @if (isset($analysis->metadata_details['metadata']['camera']['Make']) || isset($analysis->metadata_details['metadata']['camera']['Model']))
                                <div class="mt-2 pt-2 border-t border-slate-800/50 flex flex-col gap-1">
                                    <span class="text-[10px] text-blue-400 font-mono">
                                        Perangkat: {{ $analysis->metadata_details['metadata']['camera']['Make'] ?? '' }} {{ $analysis->metadata_details['metadata']['camera']['Model'] ?? '' }}
                                    </span>
                                    @if (isset($analysis->metadata_details['metadata']['camera']['LensModel']))
                                        <span class="text-[9px] text-slate-500 italic">
                                            Lensa: {{ $analysis->metadata_details['metadata']['camera']['LensModel'] }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Lapisan 2: Noise Map --}}
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800/80">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1 tracking-wider">Konsistensi Kerapatan Partikel</p>
                            <p class="text-xs font-bold leading-relaxed {{ $analysis->final_result['summary_color'] == 'success' ? 'text-emerald-400' : ($analysis->final_result['summary_color'] == 'warning' ? 'text-orange-400' : 'text-red-400') }}">
                                <i class="fa-solid {{ $analysis->final_result['summary_color'] == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' }} mr-1"></i>
                                {{ $analysis->final_result['full_report']['results']['noise']['interpretation'] ?? 'Analisis partikel selesai.' }}
                            </p>
                        </div>

                        {{-- Lapisan 3: Deepfake Detector --}}
                        <div class="flex justify-between items-center p-4 bg-slate-950 rounded-2xl border border-slate-800/80">
                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Manipulasi Wajah / AI (Deepfake)</span>
                            <span class="text-xs font-black px-3 py-1 rounded-lg tracking-wide
                                {{ $analysis->is_deepfake ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                {{ $analysis->is_deepfake ? 'TERDETEKSI / POSITIF' : 'NEGATIF / AMAN' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sistem Script View Switcher & Panel Toggle JavaScript --}}
    <script>
        function switchView(type) {
            const imgElement = document.getElementById('mainViewport');
            const btnOriginal = document.getElementById('btn-original');
            const btnEla = document.getElementById('btn-ela');
            const btnNoise = document.getElementById('btn-noise');

            // Reset class active untuk semua tombol yang ada di viewport menu
            btnOriginal.className = "px-5 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-bold transition-all duration-150 text-slate-300";
            btnEla.className = "px-5 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-bold transition-all duration-150 text-slate-300";
            btnNoise.className = "px-5 py-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-bold transition-all duration-150 text-slate-300";

            if (type === 'original') {
                imgElement.src = "{{ asset('storage/' . $analysis->s3_path) }}";
                btnOriginal.className = "px-5 py-2 bg-blue-600 hover:bg-blue-500 rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-blue-600/20";
            } else if (type === 'ela') {
                const elaFileName = "{{ $analysis->final_result['full_report']['results']['ela']['image_url'] }}";
                imgElement.src = "{{ asset('storage/results/' . auth()->id()) }}/" + elaFileName;
                btnEla.className = "px-5 py-2 bg-blue-600 hover:bg-blue-500 rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-blue-600/20";
            } else if (type === 'noise') {
                // SINKRONISASI JAVASCRIPT: Memanggil nama file noise map unik hasil pemrosesan Python
                const noiseFileName = "{{ $analysis->final_result['full_report']['results']['noise']['image_url'] }}";
                imgElement.src = "{{ asset('storage/results/' . auth()->id()) }}/" + noiseFileName;
                btnNoise.className = "px-5 py-2 bg-blue-600 hover:bg-blue-500 rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-blue-600/20";
            }
        }

        function toggleResearchPanel() {
            const panel = document.getElementById('research-panel');
            const chevron = document.getElementById('research-chevron');
            
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                panel.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }
    </script>
@endsection