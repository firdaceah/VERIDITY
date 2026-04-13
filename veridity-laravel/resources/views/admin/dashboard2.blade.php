@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
        <div>
            <h1 class="text-3xl font-bold">Welcome back, Admin Firda!</h1>
            <p class="text-slate-400 text-sm mt-1 italic">Monitoring Forensic Activity & Integration Logs.</p>
        </div>
        <div class="flex gap-3 text-xs">
            <div class="bg-emerald-500/10 text-emerald-400 px-4 py-2 rounded-lg border border-emerald-500/30">
                <i class="fa-solid fa-circle text-[8px] mr-2"></i> System: Optimal
            </div>
            <div class="bg-blue-500/10 text-blue-400 px-4 py-2 rounded-lg border border-blue-500/30">
                4-Layers Engine: Active
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Total Audit</div>
            <div class="text-3xl font-bold">{{ $totalAudit }}</div>
            <div class="text-emerald-500 text-xs mt-2 font-medium"><i class="fa-solid fa-arrow-up"></i> 12% dari minggu lalu
            </div>
        </div>
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl border-l-4 border-l-red-500">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Fraud Detected</div>
            <div class="text-3xl font-bold text-red-500">{{ $fraudCount }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium">Auto-Cancelled Orders</div>
        </div>
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Connected Clients</div>
            <div class="text-3xl font-bold">{{ $totalUser }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium">Apps & Merchants</div>
        </div>
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl">
            <div class="text-slate-500 text-xs font-bold uppercase mb-2">Mobile Plugins</div>
            <div class="text-3xl font-bold">{{ $totalUser }}</div>
            <div class="text-slate-400 text-xs mt-2 italic font-medium">Active Installations</div>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-clock-rotate-left mr-2 text-blue-500"></i> Recent Forensic
                Audit</h3>
            {{-- <button class="text-xs text-blue-400 hover:underline">View All Logs</button> --}}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                    <tr>
                        <th class="px-6 py-4">Client ID</th>
                        <th class="px-6 py-4">Object Type</th>
                        <th class="px-6 py-4">Confidence Score</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Action Taken</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($recentAudits as $audit)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 font-medium italic text-blue-400">
                                {{ $audit->user->name ?? 'Unknown' }}
                            </td>

                            <td class="px-6 py-4 text-xs text-slate-300">
                                {{ Str::limit($audit->image_name, 20) }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="text-xs {{ $audit->ela_score > 50 ? 'text-red-500' : 'text-emerald-500' }} font-bold">
                                    ELA: {{ $audit->ela_score }}%
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                {{-- Ambil label dari dalam array final_result --}}
                                @php
                                    $label = $audit->final_result['summary_label'] ?? 'Aman';
                                @endphp

                                @if ($label == 'Sangat Berbahaya')
                                    {{-- Sesuaikan teksnya dengan yang ada di Controller --}}
                                    <span
                                        class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-bold border border-red-500/20">
                                        BAHAYA
                                    </span>
                                @elseif($label == 'Mencurigakan / Warning')
                                    <span
                                        class="bg-orange-500/10 text-orange-500 px-3 py-1 rounded-full text-[10px] font-bold border border-orange-500/20">
                                        MENCURIGAKAN
                                    </span>
                                @else
                                    <span
                                        class="bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-full text-[10px] font-bold border border-emerald-500/20">
                                        AMAN
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 italic text-slate-500 text-[10px]">
                                {{ $audit->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500 italic">Belum ada aktivitas
                                analisis masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
