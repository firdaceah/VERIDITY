@extends('layouts.user')

@section('title', 'Riwayat Audit')

@section('content')
    <div class="mb-10">
        <h1 class="text-3xl font-bold italic">My <span class="text-blue-500">Audits</span></h1>
        <p class="text-slate-400">Daftar riwayat analisis forensik yang telah Anda lakukan.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($myAudits as $audit)
            <div
                class="bg-slate-900 border border-slate-800 rounded-[2rem] overflow-hidden group hover:border-blue-500/50 transition-all">
                <div class="aspect-video bg-slate-800 relative overflow-hidden">
                    <img src="{{ asset('storage/' . $audit->s3_path) }}"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                        alt="{{ $audit->image_name }}">

                    <i class="fa-solid fa-image absolute inset-0 m-auto text-slate-700 text-4xl -z-10"></i>

                    <div class="absolute top-4 right-4">
                        <span
                            class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ ($audit->final_result['summary_label'] ?? 'Aman') == 'Aman' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                            {{ $audit->final_result['summary_label'] ?? 'Aman' }}
                        </span>
                    </div>
                </div>
                <div class="p-6 text-left">
                    <h4 class="font-bold text-slate-200 mb-1 truncate">{{ $audit->image_name }}</h4>
                    <p class="text-[10px] text-slate-500 mb-4 italic">{{ $audit->created_at->format('d M Y, H:i') }}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-mono text-blue-400">Score: {{ $audit->ela_score }}%</span>
                        <a href="{{ route('user.result', $audit->id) }}"
                            class="text-xs font-bold text-white hover:text-blue-400 transition">Lihat Detail
                            →</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-20 text-center bg-slate-900/50 rounded-[2rem] border border-dashed border-slate-800">
                <p class="text-slate-500 italic">Anda belum pernah melakukan audit. Ayo mulai sekarang!</p>
            </div>
        @endforelse
    </div>
@endsection
