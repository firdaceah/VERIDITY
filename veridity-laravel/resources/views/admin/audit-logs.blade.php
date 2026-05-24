@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
    <div class="mb-8 text-slate-100">
        <h1 class="text-3xl font-bold italic">Forensic <span class="text-blue-500">Audit Logs</span></h1>
        <p class="text-slate-400 text-sm mt-1">Menampilkan seluruh riwayat deteksi manipulasi gambar.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold border-b border-slate-800/40">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Image Name</th>
                        <th class="px-6 py-4 text-center">ELA Score</th>
                        <th class="px-6 py-4 text-center">Result</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($logs as $log)
                        @php
                            $label = $log->final_result['summary_label'] ?? 'PROSES AUDIT';
                            $color = $log->final_result['summary_color'] ?? 'success';
                        @endphp
                        <tr class="hover:bg-slate-800/20 transition duration-150">
                            <td class="px-6 py-4 text-slate-500 font-mono">{{ $log->created_at->format('d M Y, H:i') }} WIB</td>
                            <td class="px-6 py-4 font-bold text-blue-400">{{ $log->user->name ?? 'System/Guest' }}</td>
                            <td class="px-6 py-4 text-slate-300 font-mono truncate max-w-[200px]" title="{{ $log->image_name }}">{{ $log->image_name }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="{{ $color == 'success' ? 'text-emerald-400' : ($color == 'warning' ? 'text-orange-400' : 'text-red-400') }} font-mono font-bold">
                                    {{ number_format($log->ela_score, 2) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{-- FIX BADGE SINKRON: Menggunakan background solid penuh agar kontras di dashboard admin --}}
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider text-white shadow-sm border
                                    {{ $color == 'success' 
                                        ? 'bg-emerald-600 border-emerald-500' 
                                        : ($color == 'warning' 
                                            ? 'bg-orange-500 border-orange-400' 
                                            : 'bg-red-600 border-red-500') }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.audit.show', $log->id) }}"
                                    class="inline-flex w-8 h-8 items-center justify-center bg-slate-800 hover:bg-slate-700 rounded-xl transition duration-150 focus:outline-none"
                                    title="Lihat Detail Log Analisis">
                                    <i class="fa-solid fa-eye text-blue-400 text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center text-slate-500 italic">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-solid fa-folder-open text-xl opacity-40"></i>
                                    <p>Data log audit forensik masih kosong.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if (method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="p-6 bg-slate-950/30 border-t border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection