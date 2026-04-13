@extends('layouts.admin')

@section('title', 'Fraud Repository')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold italic">Fraud <span class="text-red-500">Repository</span></h1>
        <p class="text-slate-400 text-sm mt-1">Database bukti manipulasi yang berhasil dicegah oleh sistem VeriDity.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                <tr>
                    <th class="px-6 py-4">Evidence Image</th>
                    <th class="px-6 py-4">Detection Score</th>
                    <th class="px-6 py-4">Reporter (User)</th>
                    <th class="px-6 py-4">Threat Type</th>
                    <th class="px-6 py-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-xs">
                @forelse($fraudCases as $case)
                    <tr class="hover:bg-red-500/5 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 bg-slate-800 rounded-lg border border-slate-700 overflow-hidden flex items-center justify-center relative group">
                                    {{-- TAMPILKAN THUMBNAIL ASLI --}}
                                    <img src="{{ asset('storage/' . $case->s3_path) }}"
                                        class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition">
                                    <i class="fa-solid fa-image text-slate-600 absolute -z-10"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-300">{{ Str::limit($case->image_name, 20) }}</span>
                                    <span class="text-[9px] text-slate-600">ID: #VRD-{{ $case->id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-red-500 font-bold">
                            ELA: {{ $case->ela_score }}%
                        </td>
                        <td class="px-6 py-4 text-slate-400">
                            <span class="italic">{{ $case->user->name ?? 'Guest User' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="bg-red-500/20 text-red-400 px-2 py-0.5 rounded text-[9px] font-bold border border-red-500/30 uppercase">
                                {{-- Jika noise_status kosong, tampilkan default threat --}}
                                {{ $case->noise_status && $case->noise_status != 'Normal' ? $case->noise_status : 'Potential Tampering' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('user.result', $case->id) }}"
                                class="inline-block bg-slate-800 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg transition text-[10px] font-bold">
                                View Evidence
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center text-slate-500 italic">Belum ada data penipuan yang
                            tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
