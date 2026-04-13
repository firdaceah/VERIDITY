@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold italic">Forensic <span class="text-blue-500">Audit Logs</span></h1>
        <p class="text-slate-400 text-sm mt-1">Menampilkan seluruh riwayat deteksi manipulasi gambar.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Image Name</th>
                        <th class="px-6 py-4 text-center">ELA Score</th>
                        <th class="px-6 py-4 text-center">Result</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-xs">
                    @forelse($logs as $log)
                        @php
                            // Ambil label dari JSON agar pengecekan @if benar
                            $label = $log->final_result['summary_label'] ?? 'Aman';
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 text-slate-500">{{ $log->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 font-bold text-blue-400">{{ $log->user->name ?? 'System' }}</td>
                            <td class="px-6 py-4 italic text-slate-300 truncate max-w-[200px]">{{ $log->image_name }}</td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="{{ $log->ela_score > 20 ? 'text-red-500' : 'text-emerald-500' }} font-mono font-bold">
                                    {{ $log->ela_score }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($label == 'Sangat Berbahaya')
                                    <span
                                        class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full font-bold border border-red-500/20">BAHAYA</span>
                                @elseif($label == 'Mencurigakan / Warning')
                                    <span
                                        class="bg-orange-500/10 text-orange-500 px-3 py-1 rounded-full font-bold border border-orange-500/20">WARNING</span>
                                @else
                                    <span
                                        class="bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-full font-bold border border-emerald-500/20">AMAN</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.audit.show', $log->id) }}"
                                    class="inline-block bg-slate-800 hover:bg-slate-700 p-2 rounded-lg transition">
                                    <i class="fa-solid fa-eye text-blue-400"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center text-slate-500 italic">Data audit masih kosong.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="p-6 bg-slate-950/30 border-t border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
