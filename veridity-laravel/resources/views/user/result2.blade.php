@extends('layouts.user')

@section('title', 'Hasil Analisis')

@section('content')
    <div class="max-w-6xl mx-auto">
        <div
            class="p-6 rounded-3xl mb-8 flex items-center justify-between 
            {{ $analysis->final_result['summary_color'] == 'warning'
                ? 'bg-orange-500/20 border border-orange-500/50'
                : ($analysis->final_result['summary_color'] == 'danger'
                    ? 'bg-red-500/20 border border-red-500/50'
                    : 'bg-emerald-500/20 border border-emerald-500/50') }}">

            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl 
                    {{ $analysis->final_result['summary_color'] == 'warning'
                        ? 'bg-orange-500'
                        : ($analysis->final_result['summary_color'] == 'danger'
                            ? 'bg-red-500'
                            : 'bg-emerald-500') }}">
                    <i
                        class="fa-solid {{ ($analysis->final_result['summary_label'] ?? '') == 'Aman' ? 'fa-check-double' : 'fa-triangle-exclamation' }}"></i>
                </div>
                <div>
                    <h2 class="font-bold text-xl uppercase tracking-tighter">
                        Hasil: {{ $analysis->final_result['summary_label'] ?? 'Unknown' }}
                    </h2>
                    <p class="text-xs opacity-70 italic">ID Analisis: #VRD-{{ $analysis->id }}</p>
                </div>
            </div>
            <button class="bg-white/10 hover:bg-white/20 px-6 py-2 rounded-xl text-xs font-bold transition">
                Unduh Laporan PDF
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900 p-4 rounded-[2.5rem] border border-slate-800">
                    <p class="text-[10px] uppercase font-bold text-slate-500 mb-4 px-4 tracking-widest italic">Visual
                        Analysis Viewport</p>

                    <img id="mainViewport" src="{{ asset('storage/' . $analysis->s3_path) }}"
                        class="w-full rounded-2xl transition-all duration-700 shadow-2xl" alt="Analyzed Image">

                    <div class="flex gap-2 mt-4 overflow-x-auto pb-2">
                        <button onclick="switchView('original')" id="btn-original"
                            class="px-4 py-2 bg-blue-600 rounded-lg text-[10px] font-bold transition">Original</button>
                        <button onclick="switchView('ela')" id="btn-ela"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-[10px] font-bold transition">ELA
                            Map</button>
                        <button onclick="switchView('noise')" id="btn-noise"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-[10px] font-bold transition">Noise
                            Map</button>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-slate-900 p-8 rounded-[2.5rem] border border-slate-800">
                    <h4 class="font-bold mb-6 italic text-blue-500">Forensic Metrics</h4>
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between text-[10px] mb-2 font-bold uppercase">
                                <span>Error Level (ELA)</span>
                                <span
                                    class="{{ $analysis->ela_score > 50 ? 'text-red-500' : 'text-emerald-500' }} font-mono">
                                    {{ $analysis->ela_score }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-800 h-1.5 rounded-full">
                                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-1000"
                                    style="width: {{ $analysis->ela_score }}%"></div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">Metadata Scan</p>
                            <p class="text-xs italic text-slate-300">
                                {{ $analysis->metadata_details['summary']['status'] ?? 'No Metadata Found' }}
                            </p>
                        </div>

                        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">Noise Analysis</p>
                            <p class="text-xs italic text-orange-400">
                                {{ $analysis->noise_status ?? 'Normal / No Issues' }}
                            </p>
                        </div>

                        <div class="flex justify-between items-center p-4 bg-slate-950 rounded-2xl border border-slate-800">
                            <span class="text-[10px] text-slate-500 uppercase font-bold">Deepfake Detect</span>
                            <span
                                class="text-xs font-bold {{ $analysis->is_deepfake ? 'text-red-500' : 'text-emerald-500' }}">
                                {{ $analysis->is_deepfake ? 'POSITIVE' : 'NEGATIVE' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchView(type) {
            const imgElement = document.getElementById('mainViewport');
            const btns = {
                original: document.getElementById('btn-original'),
                ela: document.getElementById('btn-ela'),
                noise: document.getElementById('btn-noise')
            };

            // Reset all buttons to slate
            Object.values(btns).forEach(btn => {
                btn.classList.remove('bg-blue-600');
                btn.classList.add('bg-slate-800');
            });

            // Set active button and change image
            if (type === 'original') {
                imgElement.src = "{{ asset('storage/' . $analysis->s3_path) }}";
                btns.original.classList.replace('bg-slate-800', 'bg-blue-600');
            } else if (type === 'ela') {
                imgElement.src = "{{ asset('storage/results/' . auth()->id() . '/ela_result.jpg') }}";
                btns.ela.classList.replace('bg-slate-800', 'bg-blue-600');
            } else if (type === 'noise') {
                imgElement.src = "{{ asset('storage/results/' . auth()->id() . '/temp_noise_map.png') }}";
                btns.noise.classList.replace('bg-slate-800', 'bg-blue-600');
            }
        }
    </script>
@endsection
