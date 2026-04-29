@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="mb-10">
        <h1 class="text-3xl font-bold italic">Admin <span class="text-blue-500">Overview</span></h1>
        <p class="text-slate-400 text-sm mt-1 italic">Pusat monitoring aktivitas audit forensik seluruh pengguna.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        {{-- READ: Total Data --}}
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Total Analisis</div>
            <div class="text-3xl font-bold">{{ $totalAudit }}</div>
            <div class="text-blue-500 text-xs mt-2 font-medium">Data masuk ke sistem</div>
        </div>
        
        {{-- READ: Data Bahaya --}}
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl border-l-4 border-l-red-500 shadow-lg shadow-red-500/5">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Fraud Terdeteksi</div>
            <div class="text-3xl font-bold text-red-500">{{ $fraudCount }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium">Gambar hasil manipulasi</div>
        </div>

        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Total Pengguna</div>
            <div class="text-3xl font-bold">{{ $totalUser }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium">Akun terdaftar</div>
        </div>

        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Server Storage</div>
            <div class="text-3xl font-bold text-emerald-500">Active</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium">Cloud Files: OK</div>
        </div>
    </div>

    {{-- TABLE: Monitoring Aktivitas Terbaru --}}
    <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-8 border-b border-slate-800 flex justify-between items-center">
            <h3 class="font-bold text-lg italic"><i class="fa-solid fa-bolt mr-2 text-blue-500"></i> Live Forensic Traffic</h3>
            <a href="{{ route('admin.audit-logs') }}" class="text-[10px] bg-blue-600 px-4 py-2 rounded-xl font-bold uppercase tracking-wider">Semua Log</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                    <tr>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Object Name</th>
                        <th class="px-6 py-4">Result</th>
                        <th class="px-6 py-4 text-center italic">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($recentAudits as $audit)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 font-bold text-blue-400">{{ $audit->user->name ?? 'User' }}</td>
                            <td class="px-6 py-4 text-xs text-slate-300 truncate max-w-[150px]">{{ $audit->image_name }}</td>
                            <td class="px-6 py-4">
                                @php $label = $audit->final_result['summary_label'] ?? 'Aman'; @endphp
                                <span class="px-3 py-1 rounded-lg text-[9px] font-bold uppercase border
                                    {{ $label == 'Aman' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20' }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center italic text-slate-500 text-[10px]">{{ $audit->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-slate-600">No activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection