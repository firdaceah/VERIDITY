@extends('layouts.admin')

@section('title', 'Detail Audit System')

@section('content')
    <div class="max-w-5xl mx-auto text-slate-100">
        {{-- Tombol Navigasi Kembali --}}
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('admin.audit-logs') }}" class="text-slate-400 hover:text-white text-sm font-bold transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Log
            </a>
            <span class="px-4 py-1.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-xl text-[10px] font-mono font-bold uppercase tracking-wider">
                System Log ID: #{{ $audit->id }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Kolom Kiri: Meta Informasi Berkas Bukti --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-[2rem] p-5 shadow-xl">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-4 italic tracking-widest">Evidence Preview</p>
                    
                    <div class="overflow-hidden rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center min-h-[150px] mb-4">
                        <img src="{{ asset('storage/' . $audit->s3_path) }}" class="w-full h-auto object-contain shadow-inner">
                    </div>

                    <div class="space-y-3 text-xs border-t border-slate-800/60 pt-4 font-mono">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 text-[11px]">Uploader:</span>
                            <span class="text-blue-400 font-bold font-sans">{{ $audit->user->name ?? 'System/Guest' }}</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-slate-500 text-[11px]">File Name:</span>
                            <span class="text-slate-300 break-all text-[11px]" title="{{ $audit->image_name }}">{{ $audit->image_name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 text-[11px]">Timestamp:</span>
                            <span class="text-slate-400 text-[11px]">{{ $audit->created_at->format('d/m/Y H:i') }} WIB</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Hasil Kompilasi Metrik Teknis Python --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-[2rem] p-6 shadow-xl">
                    <h3 class="text-base font-bold mb-4 italic text-blue-500 uppercase tracking-wider">// Technical Analysis Results</h3>
                    
                    @php
                        $color = $audit->final_result['summary_color'] ?? 'success';
                        // Mengambil skor metrik GAN rill dikalikan 100 untuk presentase
                        $ganPercent = ($audit->final_result['full_report']['results']['ai_detection']['metrics']['gan_score'] ?? 0) * 100;
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        {{-- ELA Metrik --}}
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1 tracking-wider">ELA ANOMALY SCORE</p>
                            <p class="text-xl font-bold font-mono {{ $color == 'success' ? 'text-emerald-400' : ($color == 'warning' ? 'text-orange-400' : 'text-red-400') }}">
                                {{ number_format($audit->ela_score, 4) }}%
                            </p>
                        </div>
                        {{-- GAN Metrik --}}
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1 tracking-wider">AI GAN FINGERPRINT</p>
                            <p class="text-xl font-bold font-mono {{ $ganPercent > 50 ? 'text-red-400' : 'text-emerald-400' }}">
                                {{ number_format($ganPercent, 2) }}%
                            </p>
                        </div>
                    </div>

                    {{-- Blok Raw JSON Dump Terstruktur --}}
                    <p class="text-[10px] text-slate-500 uppercase font-bold mb-2 tracking-widest italic">Raw JSON Output (Python Tool)</p>
                    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800/80 relative">
                        <pre class="text-[11px] text-blue-400 font-mono overflow-x-auto h-64 custom-scrollbar leading-relaxed" style="white-space: pre-wrap; word-wrap: break-word;">{{ json_encode($audit->final_result['full_report'] ?? $audit->final_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection