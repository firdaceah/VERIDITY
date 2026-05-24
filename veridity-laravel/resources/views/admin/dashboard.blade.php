@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="mb-10">
        <h1 class="text-3xl font-bold italic">Admin <span class="text-blue-500">Overview</span></h1>
        <p class="text-slate-400 text-sm mt-1 italic">Pusat monitoring aktivitas audit forensik seluruh pengguna.</p>
    </div>

    {{-- KARTU STATISTIK UTAMA --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        {{-- Total Analisis Gambar --}}
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl transition hover:border-slate-700">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Total Analisis</div>
            <div class="text-3xl font-bold font-mono">{{ $totalAudit }}</div>
            <div class="text-blue-500 text-xs mt-2 font-medium"><i class="fa-solid fa-database mr-1"></i> Data masuk ke sistem</div>
        </div>
        
        {{-- Total Fraud Terdeteksi --}}
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl border-l-4 border-l-red-500 shadow-lg shadow-red-500/5 transition hover:border-slate-700">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Fraud Terdeteksi</div>
            <div class="text-3xl font-bold text-red-500 font-mono">{{ $fraudCount }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Gambar hasil manipulasi</div>
        </div>

        {{-- Total Pengguna Terdaftar --}}
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl transition hover:border-slate-700">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Total Pengguna</div>
            <div class="text-3xl font-bold font-mono">{{ $totalUser }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium"><i class="fa-solid fa-users mr-1"></i> Akun terdaftar</div>
        </div>

        {{-- Status Server Storage --}}
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl transition hover:border-slate-700">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Server Storage</div>
            <div class="text-3xl font-bold text-emerald-500 flex items-center gap-2">
                Active
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            </div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium"><i class="fa-solid fa-hard-drive mr-1"></i> Cloud Files: OK</div>
        </div>
    </div>

    {{-- TABLE: Monitoring Aktivitas Terbaru --}}
    <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-8 border-b border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="font-bold text-lg italic"><i class="fa-solid fa-bolt mr-2 text-blue-500"></i> Live Forensic Traffic</h3>
                <p class="text-slate-500 text-xs mt-0.5">Memantau riwayat audit terakhir yang dieksekusi oleh pengguna.</p>
            </div>
            <a href="{{ route('admin.audit-logs') }}" class="text-[10px] bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl font-bold uppercase tracking-wider transition shadow-lg shadow-blue-600/10 focus:outline-none">
                Semua Log <i class="fa-solid fa-chevron-right ml-1"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold border-b border-slate-800/40">
                    <tr>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Object Name</th>
                        <th class="px-6 py-4">Result Indicator</th>
                        <th class="px-6 py-4 text-center italic">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse ($recentAudits as $audit)
                        <tr class="hover:bg-slate-800/20 transition duration-150">
                            {{-- Nama Pengguna --}}
                            <td class="px-6 py-4 font-bold text-blue-400">
                                {{ $audit->user->name ?? 'Unknown User' }}
                            </td>
                            
                            {{-- Nama Berkas Gambar --}}
                            <td class="px-6 py-4 text-xs text-slate-300 font-mono truncate max-w-[200px]" title="{{ $audit->image_name }}">
                                {{ $audit->image_name }}
                            </td>
                            
                            {{-- Status Hasil Audit (Sinkronisasi Tipe Data JSON & Standar Warna Veridity) --}}
                            <td class="px-6 py-4">
                                @php 
                                    $label = $audit->final_result['summary_label'] ?? 'PROSES AUDIT'; 
                                    $color = $audit->final_result['summary_color'] ?? 'success';
                                @endphp
                                
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider text-white shadow-sm border
                                    {{ $color == 'success' 
                                        ? 'bg-emerald-600 border-emerald-500' 
                                        : ($color == 'warning' 
                                            ? 'bg-orange-500 border-orange-400' 
                                            : 'bg-red-600 border-red-500') }}">
                                    {{ $label }}
                                </span>
                            </td>
                            
                            {{-- Waktu Masuk --}}
                            <td class="px-6 py-4 text-center italic text-slate-500 text-[10px] font-mono">
                                {{ $audit->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-16 text-center text-slate-600">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-solid fa-folder-open text-2xl opacity-40"></i>
                                    <p class="text-sm italic">Belum ada traffic aktivitas forensik saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection