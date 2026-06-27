@extends('layouts.user')

@section('title', 'Hasil Analisis')

@section('content')
    @php
        // 1. Identifikasi Ekstensi dan Tipe Objek Forensik
        $fileExtension = strtolower(pathinfo($analysis->image_name, PATHINFO_EXTENSION));
        $isDocument = in_array($fileExtension, ['pdf', 'docx']);

        // 2. Deklarasi Safe-Defensive Variables (Anti-Error Global)
        if (!$isDocument) {
            // Kalkulasi khusus jika objek adalah Citra Gambar
            $elaAuthScore = max(0, 100 - ($analysis->ela_score * 3));
            $ganScoreRaw = $analysis->final_result['full_report']['results']['ai_detection']['metrics']['gan_score'] ?? 0;
            $aiAuthScore = 100 - ($ganScoreRaw * 100);
            $metaScore = $analysis->final_result['full_report']['results']['metadata']['summary']['authenticity_score'] ?? 100;
            $noiseAuthScore = $analysis->final_result['full_report']['results']['noise']['metrics']['noise_authenticity_score'] ?? 100;
            
            // Dummy Document Fallback agar tidak memicu Undefined Variable saat merender HTML Kanan
            $humanP = 0; $aiP = 0; $hybridP = 0;
        } else {
            // Kalkulasi khusus jika objek adalah Dokumen Teks NLP
            $report = $analysis->final_result['full_report'] ?? [];
            $humanP = $report['results']['document']['metrics']['human_p'] ?? 0;
            $aiP = $report['results']['document']['metrics']['ai_p'] ?? 0;
            $hybridP = $report['results']['document']['metrics']['hybrid_p'] ?? 0;

            // Dummy Image Fallback agar aman dari jerat error halaman
            $elaAuthScore = 0; $ganScoreRaw = 0; $aiAuthScore = 0; $metaScore = 0; $noiseAuthScore = 0;
        }
    @endphp

    <div class="max-w-6xl mx-auto text-slate-100">
        {{-- Navigasi Kembali --}}
        <div class="mb-6">
            <a href="{{ route('user.my-audits') }}" class="text-[#39D2DD] hover:text-[#39D2DD] font-bold text-sm transition">
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
                    <p class="text-xs opacity-60">Kode Investigasi: #VRD-{{ $analysis->id }}</p>
                </div>
            </div>
            
            {{-- Tombol Unduh Laporan PDF Dinamis --}}
            <a href="{{ route('audit.download-pdf', $analysis->id) }}" 
               class="bg-white/10 hover:bg-white/20 text-white px-6 py-2 rounded-xl text-xs font-bold transition duration-200 decoration-none inline-block">
                <i class="fa-solid fa-file-pdf mr-1"></i> Unduh Laporan PDF
            </a>
        </div>

        {{-- Layout Utama Viewport dan Metrik Dasar --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Panel Kiri: Ruang Visual Viewport (Kondisional Citra vs Dokumen) --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-[#0E0E20] p-4 rounded-[2.5rem] border border-white/10">
                    
                    @if($isDocument)
                        {{-- Tampilan Viewport Khusus File PDF / DOCX --}}
                        <p class="text-[10px] uppercase font-bold text-slate-500 mb-4 px-4 tracking-widest italic">Document Content Extract Viewport</p>
                        <div class="relative overflow-hidden rounded-2xl bg-[#111028] p-6 flex flex-col items-center justify-center min-h-[350px] border border-white/10">
                            <div class="w-20 h-20 rounded-2xl bg-[#39D2DD]/10 flex items-center justify-center text-[#39D2DD] text-3xl mb-4 animate-pulse">
                                <i class="fa-solid {{ $fileExtension == 'pdf' ? 'fa-file-pdf' : 'fa-file-word' }}"></i>
                            </div>
                            <h3 class="font-bold text-sm text-slate-200 tracking-tight text-center max-w-md truncate">{{ $analysis->image_name }}</h3>
                            <p class="text-[11px] text-slate-500 mt-1 uppercase font-mono tracking-wider">Format: {{ $fileExtension }} | Status: Terurai Berhasil</p>
                            
                            {{-- Preview Isi Konten Dokumen Pendek --}}
                            <div class="w-full mt-5 p-4 bg-[#0E0E20]/60 rounded-xl border border-white/10 text-[11px] text-slate-400 font-sans leading-relaxed max-h-32 overflow-y-auto italic">
                                "Sistem mendeteksi untaian linguistik kalimat dokumen ini memiliki pola sebaran teks yang bersifat konstan di beberapa kluster paragraf utama..."
                            </div>
                        </div>
                    @else
                        {{-- Tampilan Viewport Khusus Citra Gambar --}}
                        <p class="text-[10px] uppercase font-bold text-slate-500 mb-4 px-4 tracking-widest italic">Visual Analysis Viewport</p>
                        <div class="relative overflow-hidden rounded-2xl bg-[#111028] flex items-center justify-center min-h-[300px]">
                            @if (app(\App\Services\EvidenceStorage::class)->exists($analysis->s3_path))
                                <img id="mainViewport" src="{{ route('files.public', ['path' => $analysis->s3_path]) }}"
                                    class="w-full h-auto max-h-[500px] object-contain transition-all duration-500 shadow-2xl" alt="Analyzed Image">
                            @else
                                <div id="mainViewport" class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                    <i class="fa-regular fa-image text-5xl"></i>
                                    <span class="text-[11px] font-bold uppercase tracking-wider">File gambar tidak tersedia</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-2 mt-4 overflow-x-auto pb-2">
                            <button onclick="switchView('original')" id="btn-original"
                                class="px-5 py-2 bg-[#4338CA] hover:bg-[#39D2DD] rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-[#4338CA]/20">Foto Orisinal</button>
                            <button onclick="switchView('ela')" id="btn-ela"
                                class="px-5 py-2 bg-[#1D143E] hover:bg-[#251549] rounded-xl text-xs font-bold transition-all duration-150 text-slate-300">Peta Piksel (ELA Map)</button>
                            <button onclick="switchView('noise')" id="btn-noise"
                                class="px-5 py-2 bg-[#1D143E] hover:bg-[#251549] rounded-xl text-xs font-bold transition-all duration-150 text-slate-300">Kerapatan Residu (Noise Map)</button>
                        </div>
                    @endif
                </div>

                {{-- Mode Detail Analis Forensik (Accordion Toggle untuk Dosen/Peneliti) --}}
                <div class="bg-[#0E0E20] rounded-[2rem] border border-white/10 overflow-hidden">
                    <button onclick="toggleResearchPanel()" class="w-full p-6 flex items-start sm:items-center justify-between gap-4 text-left hover:bg-[#1D143E]/40 transition duration-200 focus:outline-none">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#39D2DD]/10 flex items-center justify-center text-[#39D2DD] shrink-0">
                                <i class="fa-solid fa-microscope"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-100">Mode Detail Analis Forensik</h4>
                                <p class="text-[11px] text-slate-500 mt-1">Untuk peneliti/dosen: kalkulasi matematis, batas ambang parameter, dan verifikasi silang metode.</p>
                            </div>
                        </div>
                        <i id="research-chevron" class="fa-solid fa-chevron-down text-slate-400 transition-transform duration-300 mt-1 sm:mt-0"></i>
                    </button>

                    <div id="research-panel" class="hidden border-t border-white/10 bg-[#111028] p-5 sm:p-6 space-y-8 text-xs text-slate-300 font-sans">
                        
                        @if($isDocument)
                            {{-- ========================================================================= --}}
                            {{-- KONTEN DETAIL ACCORDION KHUSUS DOKUMEN TEKS --}}
                            {{-- ========================================================================= --}}
                            <div class="border-b border-white/10 pb-6 space-y-2">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-[#39D2DD] font-mono text-sm tracking-tight">NATURAL LANGUAGE PROCESSING (NLP) TEXT SEGMENTATION</h5>
                                    <span class="px-2 py-0.5 rounded bg-[#39D2DD]/10 text-[#39D2DD] font-mono text-[10px]">Probabilitas Bahasa</span>
                                </div>
                                <div class="bg-[#0E0E20]/60 p-4 rounded-2xl border border-white/10 font-mono text-slate-400">
                                    <p class="text-xs text-slate-300 font-bold mb-1.5">Kalkulasi Distribusi Kalimat:</p>
                                    <ul class="list-none space-y-1 pl-1">
                                        <li><i class="fa-solid fa-circle-dot text-emerald-400 mr-2 text-[8px]"></i>Porsi Kalimat Orisinal Manusia : <span class="text-emerald-400 font-bold">{{ $humanP }}%</span></li>
                                        <li><i class="fa-solid fa-circle-dot text-red-400 mr-2 text-[8px]"></i>Porsi Kalimat Sintetis AI Murni : <span class="text-red-400 font-bold">{{ $aiP }}%</span></li>
                                        <li><i class="fa-solid fa-circle-dot text-amber-400 mr-2 text-[8px]"></i>Porsi Kalimat Hasil Modifikasi/Hybrid : <span class="text-amber-400 font-bold">{{ $hybridP }}%</span></li>
                                    </ul>
                                    <div class="pt-2 border-t border-white/10 mt-2 text-white font-bold">
                                        <p><i class="fa-solid fa-diagram-project text-[#39D2DD] mr-2 text-[10px]"></i>Model Analisis : Lightweight Linguistic Pattern Detector</p>
                                        <p><i class="fa-solid fa-gauge-high text-[#39D2DD] mr-2 text-[10px]"></i>Skor Indikasi Linguistik : <span class="text-[#39D2DD] font-bold">{{ $analysis->final_result['full_report']['final_score'] ?? 0 }}%</span></p>
                                        <p><i class="fa-solid fa-square-root-variable text-[#39D2DD] mr-2 text-[10px]"></i>Rumus Skor : variasi kalimat, repetisi, keragaman kosakata, dan indikator struktur AI-like</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 text-[11px] pt-1">
                                    <p><span class="text-amber-400 font-bold"><i class="fa-solid fa-flask-vial mr-1.5"></i>Kesimpulan Linguistik Komputasional:</span>
                                        <span class="text-slate-300 font-semibold">{{ $analysis->final_result['full_report']['results']['document']['interpretation'] ?? 'Analisis bahasa selesai.' }}</span>
                                    </p>
                                </div>
                            </div>

                            {{-- KONSOLIDASI BOBOT NILAI DOKUMEN --}}
                            <div class="bg-[#0E0E20]/40 p-5 rounded-3xl border border-[#39D2DD]/20 space-y-4">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-white/10 pb-3 gap-2">
                                    <h5 class="font-bold text-[#39D2DD] font-mono text-xs uppercase tracking-wider">RINGKASAN INDIKATOR LINGUISTIK DOKUMEN</h5>
                                    <div class="flex flex-wrap gap-2 font-mono text-[9px]">
                                        <span class="px-2 py-0.5 rounded bg-emerald-600/20 text-emerald-400 border border-emerald-500/20 font-bold">Kemungkinan Manusia: &gt;= 80.00%</span>
                                        <span class="px-2 py-0.5 rounded bg-orange-600/20 text-orange-400 border border-orange-500/20 font-bold">Indikator Campuran: 60.00% - 79.99%</span>
                                        <span class="px-2 py-0.5 rounded bg-red-600/20 text-red-400 border border-red-500/20 font-bold">Kemungkinan AI: &lt; 60.00%</span>
                                    </div>
                                </div>

                                <div class="p-4 bg-[#111028]/80 rounded-xl border border-white/10 text-[11px] text-slate-400 space-y-3 shadow-inner">
                                    <p class="text-slate-300 font-bold border-b border-white/10 pb-1.5 font-sans">Kriteria Klasifikasi Dokumen:</p>
                                    <div class="space-y-2.5">
                                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-1 md:gap-4">
                                            <div class="md:col-span-3 font-bold text-emerald-400 flex items-center gap-2">Skor 80.00% - 100%</div>
                                            <div class="md:col-span-3 font-extrabold uppercase tracking-wide text-emerald-500">[ KEMUNGKINAN DITULIS MANUSIA ]</div>
                                            <div class="md:col-span-6 text-slate-400 text-[11px]">Teks menunjukkan variasi ritme kalimat, pilihan kata natural, dan sedikit tanda struktur seragam AI-like.</div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-1 md:gap-4 border-t border-white/10 pt-2">
                                            <div class="md:col-span-3 font-bold text-orange-400 flex items-center gap-2">Skor 60.00% - 79.99%</div>
                                            <div class="md:col-span-3 font-extrabold uppercase tracking-wide text-orange-400">[ INDIKATOR CAMPURAN ]</div>
                                            <div class="md:col-span-6 text-slate-400 text-[11px]">Terdapat campuran pola tulisan natural dan indikator struktur atau repetisi yang dapat mengarah ke bantuan AI.</div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-1 md:gap-4 border-t border-white/10 pt-2">
                                            <div class="md:col-span-3 font-bold text-red-400 flex items-center gap-2">Skor 0.00% - 59.99%</div>
                                            <div class="md:col-span-3 font-extrabold uppercase tracking-wide text-red-500">[ KEMUNGKINAN DITULIS AI ]</div>
                                            <div class="md:col-span-6 text-slate-400 text-[11px]">Banyak bagian menunjukkan pola bahasa yang repetitif, seragam, atau terlalu terstruktur dan sering berkaitan dengan teks buatan AI.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 text-xs font-mono border-t border-white/10">
                                    <span class="text-slate-500">Metode Evaluasi: <span class="text-slate-600">Lightweight Linguistic Pattern Detector</span></span>
                                    <span class="text-[#39D2DD] font-bold text-sm">Skor Indikasi Dokumen: {{ number_format($analysis->final_result['full_report']['final_score'] ?? 0, 2) }}%</span>
                                </div>
                            </div>

                        @else
                            {{-- ========================================================================= --}}
                            {{-- KONTEN DETAIL ACCORDION KHUSUS CITRA GAMBAR --}}
                            {{-- ========================================================================= --}}
                            {{-- 1. ELEMEN ELA --}}
                            <div class="border-b border-white/10 pb-6 space-y-2">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-[#39D2DD] font-mono text-sm tracking-tight">ERROR LEVEL ANALYSIS (ELA) METHOD</h5>
                                    <span class="px-2 py-0.5 rounded bg-[#39D2DD]/10 text-[#39D2DD] font-mono text-[10px]">Piksel Deviasi</span>
                                </div>
                                <div class="bg-[#0E0E20]/60 p-4 rounded-2xl border border-white/10 font-mono text-slate-400">
                                    <p class="text-xs text-slate-300 font-bold mb-1.5">Kalkulasi Parameter:</p>
                                    <ul class="list-none space-y-1 pl-1">
                                        <li>Rerata Selisih Eror (Mean Diff) : {{ number_format($analysis->final_result['full_report']['results']['ela']['metrics']['mean_diff'] ?? 0, 5) }}</li>
                                        <li>Standar Deviasi Eror (Std Dev) : {{ number_format($analysis->final_result['full_report']['results']['ela']['metrics']['std_diff'] ?? 0, 5) }}</li>
                                        <li class="text-[#39D2DD] font-bold">Rumus Deteksi Anomali : Anomaly Score = Mean + (2 * Std Dev)</li>
                                        <li>Hasil Akhir ELA Score Mentah : <span class="text-red-400 font-bold">{{ number_format($analysis->ela_score, 4) }}%</span></li>
                                    </ul>
                                    <div class="pt-2 border-t border-white/10 mt-2">
                                        <ul class="list-none space-y-1 pl-1 text-white font-bold">
                                            <li>Rumus Konversi Keaslian : A_ela = max(0, 100 - (ELA_Score * 3))</li>
                                            <li>Hasil Skor Integritas ELA : <span class="text-emerald-400 font-bold">{{ number_format($elaAuthScore, 2) }}%</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] pt-1">
                                    <p><span class="text-amber-400 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Batas Aman Toleransi:</span> ELA Score Mentah wajib <span class="font-mono bg-[#0E0E20] px-1.5 py-0.5 rounded border border-white/10 text-slate-300">&lt;= 10.00%</span> agar struktur piksel dianggap homogen.</p>
                                    <p><span class="text-emerald-400 font-bold"><i class="fa-solid fa-flask-vial mr-1.5"></i>Kesimpulan Eksperimen:</span>
                                        @if($analysis->ela_score > 10.0)
                                            <span class="text-red-400 font-bold">ANOMALI TERDETEKSI.</span> Terdapat lonjakan kontras ketebalan piksel eror yang menandakan file terindikasi mengalami manipulasi lokal (*splicing*).
                                        @else
                                            <span class="text-emerald-400 font-bold">STRUKTUR HOMOGEN.</span> Sebaran tingkat degradasi eror piksel stabil, menandakan tingkat keaslian tinggi sebesar **{{ number_format($elaAuthScore, 2) }}%**.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- 2. ELEMEN AI DETECTOR (GAN) --}}
                            <div class="border-b border-white/10 pb-6 space-y-2">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-[#7C3AED] font-mono text-sm tracking-tight">DEEPFAKE SPECTRAL ANALYSIS (GAN DETECTOR)</h5>
                                    <span class="px-2 py-0.5 rounded bg-[#7C3AED]/10 text-[#7C3AED] font-mono text-[10px]">Sidik Jari Spektral</span>
                                </div>
                                <div class="bg-[#0E0E20]/60 p-4 rounded-2xl border border-white/10 font-mono text-slate-400">
                                    <p class="text-xs text-slate-300 font-bold mb-1.5">Kalkulasi Parameter:</p>
                                    <ul class="list-none space-y-1 pl-1">
                                        <li>Varians Frekuensi Radial  : {{ number_format($analysis->final_result['full_report']['results']['ai_detection']['metrics']['radial_frequency_variance'] ?? 0, 5) }}</li>
                                        <li>Simetri Kuadran Spektrum : {{ number_format($analysis->final_result['full_report']['results']['ai_detection']['metrics']['quadrant_symmetry'] ?? 0, 5) }}</li>
                                        <li>Titik Puncak Terdeteksi : {{ $analysis->final_result['full_report']['results']['ai_detection']['metrics']['spectral_peaks_detected'] ?? 0 }} Titik</li>
                                        <li>Nilai Probabilitas GAN Mentah : <span class="text-red-400 font-bold">{{ number_format($ganScoreRaw, 4) }}</span> (Skala 0 - 1)</li>
                                    </ul>
                                    <div class="pt-2 border-t border-white/10 mt-2">
                                        <ul class="list-none space-y-1 pl-1 text-white font-bold">
                                            <li>Rumus Konversi Keaslian : A_ai = 100 - (GAN_Score * 100)</li>
                                            <li>Hasil Skor Keaslian Spektral AI : <span class="text-emerald-400 font-bold">{{ number_format($aiAuthScore, 2) }}%</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] pt-1">
                                    <p><span class="text-amber-400 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Batas Aman Toleransi:</span> Nilai probabilitas mesin idealnya wajib <span class="font-mono bg-[#0E0E20] px-1.5 py-0.5 rounded border border-white/10 text-slate-300">&lt;= 0.4500</span> agar terbebas dari jerat artifak upsampling komputer.</p>
                                    <p><span class="text-emerald-400 font-bold"><i class="fa-solid fa-flask-vial mr-1.5"></i>Kesimpulan Eksperimen:</span>
                                        @if($ganScoreRaw > 0.45)
                                            <span class="text-red-400 font-bold">POSITIF GENERATOR AI.</span> Ditemukan keselarasan simetri kuadran frekuensi yang kaku khas kecerdasan buatan, menekan tingkat keaslian alami citra menjadi **{{ number_format($aiAuthScore, 2) }}%**.
                                        @else
                                            <span class="text-emerald-400 font-bold">NEGATIF DEEPFAKE AI.</span> Tidak ditemukan sisa jejak replikasi grafis komputer, struktur wajah dinilai murni asli sensor fisik.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- 3. ELEMEN NOISE VARIANCE --}}
                            <div class="border-b border-white/10 pb-6 space-y-2">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-amber-400 font-mono text-sm tracking-tight">HIGH-PASS NOISE VARIANCE DENSITY</h5>
                                    <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 font-mono text-[10px]">Residu Kebisingan Kamera</span>
                                </div>
                                <div class="bg-[#0E0E20]/60 p-4 rounded-2xl border border-white/10 font-mono text-slate-400">
                                    <p class="text-xs text-slate-300 font-bold mb-1.5">Kalkulasi Parameter (Block-Based High-Pass Filter):</p>
                                    <ul class="list-none space-y-1 pl-1">
                                        <li>Rerata Varians Noise  : {{ number_format($analysis->final_result['full_report']['results']['noise']['metrics']['overall_variance'] ?? 0, 6) }}</li>
                                        <li>Deviasi Standar Blok  : {{ number_format($analysis->final_result['full_report']['results']['noise']['metrics']['block_variance_std'] ?? 0, 6) }}</li>
                                        <li class="text-amber-400 font-bold">Rumus Evaluasi Lensa : Deviasi Standar &gt; (Rerata Varians * 1.5)</li>
                                        <li>Channel-RGB Variance : R:{{ number_format($analysis->final_result['full_report']['results']['noise']['metrics']['channel_noise_variance']['red'] ?? 0, 6) }} | G:{{ number_format($analysis->final_result['full_report']['results']['noise']['metrics']['channel_noise_variance']['green'] ?? 0, 6) }} | B:{{ number_format($analysis->final_result['full_report']['results']['noise']['metrics']['channel_noise_variance']['blue'] ?? 0, 6) }}</li>
                                    </ul>
                                    <div class="pt-2 border-t border-white/10 mt-2">
                                        <ul class="list-none space-y-1 pl-1 text-white font-bold">
                                            <li>Rumus Konversi Keaslian : A_noise = max(20, 100 - ((Deviasi_Blok / (Rerata_Noise * 1.5)) * 20))</li>
                                            <li>Hasil Skor Kerapatan Noise : <span class="text-emerald-400 font-bold">{{ number_format($noiseAuthScore, 2) }}%</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] pt-1">
                                    <p><span class="text-amber-400 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Batas Aman Toleransi:</span> Nilai varians noise murni idealnya wajib berada di kisaran <span class="font-mono bg-[#0E0E20] px-1.5 py-0.5 rounded border border-white/10 text-slate-300">&gt;= 2.0000</span> untuk menggaransi adanya jejak buiran sensor murni asli sensor optik fisik.</p>
                                    <p><span class="text-emerald-400 font-bold"><i class="fa-solid fa-flask-vial mr-1.5"></i>Kesimpulan Eksperimen:</span>
                                        <span class="text-slate-300 font-semibold">{{ $analysis->final_result['full_report']['results']['noise']['interpretation'] ?? '' }}</span>
                                    </p>
                                </div>
                            </div>

                            {{-- 4. ELEMEN METADATA SCAN --}}
                            <div class="border-b border-white/10 pb-6 space-y-2">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-emerald-400 font-mono text-sm tracking-tight">METADATA EXIF INTEGRITY CHECK</h5>
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-mono text-[10px]">Rekam Jejak Berkas</span>
                                </div>
                                <div class="bg-[#0E0E20]/60 p-4 rounded-2xl border border-white/10 font-mono text-slate-400">
                                    <p class="text-xs text-slate-300 font-bold mb-1.5">Kalkulasi Parameter (EXIF Dictionary Parsing):</p>
                                    <ul class="list-none space-y-1 pl-1">
                                        <li>Kamera/Tipe Manufaktur : {{ $analysis->metadata_details['metadata']['camera']['Make'] ?? 'KOSONG / TIDAK TERDETEKSI' }}</li>
                                        <li>Software Ekspor Vendor  : {{ $analysis->metadata_details['metadata']['software']['Software'] ?? 'MURNI OPTIK / TANPA APPLIKASI' }}</li>
                                        <li class="text-[#39D2DD] font-bold">Pinalti Pengurangan : Hilang Kamera (-30 Poin) | Jejak Aplikasi Editor (-20 Poin)</li>
                                    </ul>
                                    <div class="pt-2 border-t border-white/10 mt-2">
                                        <ul class="list-none space-y-1 pl-1 text-white font-bold">
                                            <li>Rumus Konversi Keaslian : A_meta = 100 - Total_Pinalti_Anomali</li>
                                            <li>Hasil Skor Otentikasi Metadata : <span class="text-emerald-400 font-bold">{{ number_format($metaScore, 2) }}%</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] pt-1">
                                    <p><span class="text-amber-400 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Batas Aman Toleransi:</span> Minimal <span class="font-mono bg-[#0E0E20] px-1.5 py-0.5 rounded border border-white/10 text-slate-300">85 Poin</span>. Jika skor jatuh akibat deteksi riwayat editor, berkas dikategorikan rentan atau pernah mengalami pemrosesan ekspor ulang aplikasi eksternal.</p>
                                    <p><span class="text-emerald-400 font-bold"><i class="fa-solid fa-flask-vial mr-1.5"></i>Kesimpulan Eksperimen:</span>
                                        <span class="text-slate-300 font-semibold">{{ $analysis->metadata_details['summary']['status'] ?? 'No Status' }}.</span> Status vonis integritas riwayat tercatat sebagai berkas <span class="text-[#39D2DD] font-bold">{{ $analysis->final_result['full_report']['results']['metadata']['summary']['verdict'] ?? 'UNKNOWN' }}</span>.
                                    </p>
                                </div>
                            </div>

                            {{-- 5. KONSOLIDASI BOBOT NILAI CITRA --}}
                            <div class="bg-[#0E0E20]/40 p-6 rounded-3xl border border-[#39D2DD]/20 space-y-5 shadow-inner">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-white/10 pb-3 gap-2">
                                    <h5 class="font-bold text-[#39D2DD] font-mono text-xs uppercase tracking-wider">KONSOLIDASI BOBOT NILAI AKHIR (MATRIKS SIDANG)</h5>
                                    <div class="flex flex-wrap gap-2 font-mono text-[9px]">
                                        <span class="px-2 py-0.5 rounded bg-emerald-600/20 text-emerald-400 border border-emerald-500/20 font-bold">Otentik: &gt;= 80.00%</span>
                                        <span class="px-2 py-0.5 rounded bg-orange-600/20 text-orange-400 border border-orange-500/20 font-bold">Manipulasi: 60.00% - 79.99%</span>
                                        <span class="px-2 py-0.5 rounded bg-red-600/20 text-red-400 border border-red-500/20 font-bold">Deepfake AI: &lt; 60.00%</span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 font-mono text-[10px] text-slate-400">
                                    <div class="p-3 bg-[#111028] rounded-xl border border-white/10">
                                        <p class="text-[#39D2DD] font-bold">A. Integritas ELA</p>
                                        <p class="mt-1">Skor: {{ number_format($elaAuthScore, 2) }}%</p>
                                        <p class="text-slate-600">Bobot(30%): {{ number_format($elaAuthScore * 0.30, 2) }}%</p>
                                    </div>
                                    <div class="p-3 bg-[#111028] rounded-xl border border-white/10">
                                        <p class="text-amber-400 font-bold">B. Kerapatan Noise</p>
                                        <p class="mt-1">Skor: {{ number_format($noiseAuthScore, 2) }}%</p>
                                        <p class="text-slate-600">Bobot(30%): {{ number_format($noiseAuthScore * 0.30, 2) }}%</p>
                                    </div>
                                    <div class="p-3 bg-[#111028] rounded-xl border border-white/10">
                                        <p class="text-emerald-400 font-bold">C. EXIF Metadata</p>
                                        <p class="mt-1">Skor: {{ number_format($metaScore, 2) }}%</p>
                                        <p class="text-slate-600">Bobot(20%): {{ number_format($metaScore * 0.20, 2) }}%</p>
                                    </div>
                                    <div class="p-3 bg-[#111028] rounded-xl border border-white/10">
                                        <p class="text-[#7C3AED] font-bold">D. Spektral AI</p>
                                        <p class="mt-1">Skor: {{ number_format($aiAuthScore, 2) }}%</p>
                                        <p class="text-slate-600">Bobot(20%): {{ number_format($aiAuthScore * 0.20, 2) }}%</p>
                                    </div>
                                </div>

                                <div class="p-4 bg-[#111028]/80 rounded-xl border border-white/10 text-[11px] text-slate-400 space-y-3 shadow-inner">
                                    <p class="text-slate-300 font-bold border-b border-white/10 pb-1.5 font-sans">Kriteria Klasifikasi Berdasarkan Skor Kumulatif Akhir:</p>
                                    <div class="space-y-2.5">
                                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-1 md:gap-4">
                                            <div class="md:col-span-3 font-bold text-emerald-400 flex items-center gap-2">Skor 80.00% - 100%</div>
                                            <div class="md:col-span-3 font-extrabold uppercase tracking-wide text-emerald-500">[ AMAN / OTENTIK ]</div>
                                            <div class="md:col-span-6 text-slate-400 text-[11px]">Seluruh elemen piksel, kompresi, struktur noise, dan EXIF kamera murni terverifikasi homogen/selaras.</div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-1 md:gap-4 border-t border-white/10 pt-2">
                                            <div class="md:col-span-3 font-bold text-orange-400 flex items-center gap-2">Skor 60.00% - 79.99%</div>
                                            <div class="md:col-span-3 font-extrabold uppercase tracking-wide text-orange-400">[ MANIPULASI / EDITING ]</div>
                                            <div class="md:col-span-6 text-slate-400 text-[11px]">Gambar melewati proses modifikasi lokal (*splicing/cloning*) atau ekspor aplikasi penyunting eksternal.</div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-1 md:gap-4 border-t border-white/10 pt-2">
                                            <div class="md:col-span-3 font-bold text-red-400 flex items-center gap-2">Skor 0.00% - 59.99%</div>
                                            <div class="md:col-span-3 font-extrabold uppercase tracking-wide text-red-500">[ DEEPFAKE AI MURNI ]</div>
                                            <div class="md:col-span-6 text-slate-400 text-[11px]">Absennya grain lensa alami dan tingginya anomali *periodic artifacts* frekuensi buatan generator komputer.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 text-xs font-mono border-t border-white/10">
                                    <span class="text-slate-500">Formulasi Total: <span class="text-slate-600">(A * 0.30) + (B * 0.30) + (C * 0.20) + (D * 0.20)</span></span>
                                    <span class="text-[#39D2DD] font-bold text-sm">Skor Akhir Keaslian Citra: {{ number_format($analysis->final_result['full_report']['final_score'] ?? 0, 2) }}%</span>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Panel Kanan: Rangkuman Metrik Dasar untuk User Awam --}}
            <div class="space-y-6">
                <div class="bg-[#0E0E20] p-6 sm:p-8 rounded-[2.5rem] border border-white/10 shadow-xl">
                    <h4 class="font-bold mb-6 italic text-[#39D2DD] text-sm tracking-wider uppercase">Forensic Metrics (Rangkuman)</h4>
                    
                    <div class="space-y-6">
                        {{-- Slider Progress Ringkasan Kuantitatif (Dinamis: Dokumen vs Citra) --}}
                        <div>
                            <div class="flex justify-between text-[10px] mb-2 font-bold uppercase tracking-wide">
                                @if($isDocument)
                                    <span class="text-slate-400">Linguistic Indication Score</span>
                                    <span class="{{ ($analysis->final_result['full_report']['final_score'] ?? 100) < 60 ? 'text-red-400' : 'text-emerald-400' }} font-mono text-xs">
                                        {{ number_format($analysis->final_result['full_report']['final_score'] ?? 100, 2) }}%
                                    </span>
                                @else
                                    <span class="text-slate-400">Error Level (ELA Score)</span>
                                    <span class="{{ $analysis->ela_score > 15 ? 'text-red-400' : 'text-emerald-400' }} font-mono text-xs">
                                        {{ number_format($analysis->ela_score, 2) }}%
                                    </span>
                                @endif
                            </div>
                            <div class="w-full bg-[#1D143E] h-2 rounded-full">
                                @if($isDocument)
                                    <div class="h-2 rounded-full transition-all duration-1000 
                                        {{ ($analysis->final_result['full_report']['final_score'] ?? 100) < 60 ? 'bg-red-500' : 'bg-emerald-500' }}"
                                        style="width: {{ $analysis->final_result['full_report']['final_score'] ?? 100 }}%"></div>
                                @else
                                    <div class="h-2 rounded-full transition-all duration-1000 
                                        {{ $analysis->ela_score > 15 ? 'bg-red-500' : 'bg-emerald-500' }}"
                                        style="width: {{ min(100, max(5, $analysis->ela_score)) }}%"></div>
                                @endif
                            </div>
                        </div>

                        {{-- Info Pelengkap Kartu Kanan Dokumen vs Citra --}}
                        @if($isDocument)
                            <div class="p-4 bg-[#111028] rounded-2xl border border-white/10 space-y-2">
                                <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Linguistic Pattern Indicators</p>
                                <div class="text-xs space-y-1 font-mono">
                                    <p class="text-emerald-400"><i class="fa-solid fa-circle-check mr-1.5"></i>Natural Writing Indicators: {{ $humanP }}%</p>
                                    <p class="text-red-400"><i class="fa-solid fa-circle-xmark mr-1.5"></i>AI-Like Indicators: {{ $aiP }}%</p>
                                    <p class="text-amber-400"><i class="fa-solid fa-bolt mr-1.5"></i>Mixed Indicators: {{ $hybridP }}%</p>
                                </div>
                            </div>
                        @else
                            {{-- Lapisan 1: Metadata Citra Gambar --}}
                            <div class="p-4 bg-[#111028] rounded-2xl border border-white/10">
                                <p class="text-[10px] text-slate-500 uppercase font-bold mb-1 tracking-wider">Pemeriksaan Identitas File</p>
                                <p class="text-xs font-bold {{ $analysis->final_result['summary_color'] == 'success' ? 'text-emerald-400' : 'text-orange-400' }}">
                                    <i class="fa-solid {{ $analysis->final_result['summary_color'] == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }} mr-1"></i>
                                    {{ $analysis->metadata_details['summary']['status'] ?? 'Riwayat Kosong' }}
                                </p>
                                @if (isset($analysis->metadata_details['metadata']['camera']['Make']) || isset($analysis->metadata_details['metadata']['camera']['Model']))
                                    <div class="mt-2 pt-2 border-t border-white/10 flex flex-col gap-1">
                                        <span class="text-[10px] text-[#39D2DD] font-mono">
                                            Perangkat: {{ $analysis->metadata_details['metadata']['camera']['Make'] ?? '' }} {{ $analysis->metadata_details['metadata']['camera']['Model'] ?? '' }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Lapisan 2: Noise Map Citra Gambar --}}
                            <div class="p-4 bg-[#111028] rounded-2xl border border-white/10">
                                <p class="text-[10px] text-slate-500 uppercase font-bold mb-1 tracking-wider">Konsistensi Kerapatan Partikel</p>
                                <p class="text-xs font-bold leading-relaxed {{ $analysis->final_result['summary_color'] == 'success' ? 'text-emerald-400' : ($analysis->final_result['summary_color'] == 'warning' ? 'text-orange-400' : 'text-red-400') }}">
                                    <i class="fa-solid {{ $analysis->final_result['summary_color'] == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' }} mr-1"></i>
                                    {{ $analysis->final_result['full_report']['results']['noise']['interpretation'] ?? 'Analisis partikel selesai.' }}
                                </p>
                            </div>

                            {{-- Lapisan 3: Deepfake Detector Citra Gambar --}}
                            <div class="flex justify-between items-center p-4 bg-[#111028] rounded-2xl border border-white/10">
                                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Manipulasi Wajah / AI (Deepfake)</span>
                                <span class="text-xs font-black px-3 py-1 rounded-lg tracking-wide
                                    {{ $analysis->is_deepfake ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                    {{ $analysis->is_deepfake ? 'TERDETEKSI / POSITIF' : 'NEGATIF / AMAN' }}
                                </span>
                            </div>
                        @endif
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sistem Script View Switcher & Panel Toggle JavaScript --}}
    <script>
        function switchView(type) {
            @if(!$isDocument)
                const imgElement = document.getElementById('mainViewport');
                if(!imgElement || imgElement.tagName !== 'IMG') return;

                const btnOriginal = document.getElementById('btn-original');
                const btnEla = document.getElementById('btn-ela');
                const btnNoise = document.getElementById('btn-noise');

                btnOriginal.className = "px-5 py-2 bg-[#1D143E] hover:bg-[#251549] rounded-xl text-xs font-bold transition-all duration-150 text-slate-300";
                btnEla.className = "px-5 py-2 bg-[#1D143E] hover:bg-[#251549] rounded-xl text-xs font-bold transition-all duration-150 text-slate-300";
                btnNoise.className = "px-5 py-2 bg-[#1D143E] hover:bg-[#251549] rounded-xl text-xs font-bold transition-all duration-150 text-slate-300";

                if (type === 'original') {
                    imgElement.src = "{{ route('files.public', ['path' => $analysis->s3_path]) }}";
                    btnOriginal.className = "px-5 py-2 bg-[#4338CA] hover:bg-[#39D2DD] rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-[#4338CA]/20";
                } else if (type === 'ela') {
                    const elaFileName = "{{ $analysis->final_result['full_report']['results']['ela']['image_url'] ?? '' }}";
                    imgElement.src = "{{ route('files.public', ['path' => 'results/' . auth()->id() . '/__FILE__']) }}".replace('__FILE__', elaFileName);
                    btnEla.className = "px-5 py-2 bg-[#4338CA] hover:bg-[#39D2DD] rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-[#4338CA]/20";
                } else if (type === 'noise') {
                    const noiseFileName = "{{ $analysis->final_result['full_report']['results']['noise']['image_url'] ?? '' }}";
                    imgElement.src = "{{ route('files.public', ['path' => 'results/' . auth()->id() . '/__FILE__']) }}".replace('__FILE__', noiseFileName);
                    btnNoise.className = "px-5 py-2 bg-[#4338CA] hover:bg-[#39D2DD] rounded-xl text-xs font-bold transition-all duration-150 text-white shadow-md shadow-[#4338CA]/20";
                }
            @endif
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
