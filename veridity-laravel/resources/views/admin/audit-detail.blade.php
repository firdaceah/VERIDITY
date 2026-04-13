@extends('layouts.admin')

@section('title', 'Detail Audit System')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('admin.dashboard') }}" class="text-slate-400 hover:text-white text-sm">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Dashboard
            </a>
            <span
                class="px-4 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-full text-[10px] font-bold uppercase">
                System Log ID: #{{ $audit->id }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-4 italic">Evidence Preview</p>
                    <img src="{{ asset('storage/' . $audit->s3_path) }}"
                        class="w-full rounded-2xl mb-4 border border-slate-700">
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-slate-500">Uploader:</span><span
                                class="text-blue-400 font-bold">{{ $audit->user->name }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">File Name:</span><span
                                class="truncate ml-4">{{ $audit->image_name }}</span></div>
                        <div class="flex justify-between"><span
                                class="text-slate-500">Date:</span><span>{{ $audit->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                    <h3 class="text-lg font-bold mb-4">Technical Analysis Results</h3>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">ELA ANOMALY</p>
                            <p class="text-xl font-bold {{ $audit->ela_score > 20 ? 'text-red-500' : 'text-emerald-500' }}">
                                {{ $audit->ela_score }}%</p>
                        </div>
                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">AI GAN SCORE</p>
                            <p class="text-xl font-bold">
                                {{ ($audit->final_result['full_report']['results']['ai_detection']['gan_score'] ?? 0) * 100 }}%
                            </p>
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-500 uppercase font-bold mb-2">Raw JSON Output (Python Tool)</p>
                    <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 overflow-hidden">
                        <pre class="text-[10px] text-blue-300 font-mono overflow-x-auto h-64 custom-scrollbar">{{ json_encode($audit->final_result['full_report'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
