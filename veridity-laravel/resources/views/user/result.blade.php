<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Analisis - VeriDity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --bg-light: #f8faff;
            --sidebar-white: #ffffff;
            --border-color: #eef2f7;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            background: var(--sidebar-white);
            border-right: 1px solid var(--border-color);
            padding-top: 25px;
        }

        .sidebar .nav-link {
            color: #6c757d;
            padding: 14px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            color: var(--primary-blue);
            background: rgba(13, 110, 253, 0.05);
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="px-8 mb-10"><span class="font-bold text-blue-600 text-3xl">Veri<span
                    class="text-slate-800">Dity</span></span></div>
        <nav class="flex flex-col">
            <a class="nav-link" href="{{ route('user.dashboard') }}"><i class="fa-solid fa-house"></i> Beranda</a>
            <a class="nav-link" href="{{ route('user.history') }}"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat
                Saya</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="max-w-6xl mx-auto">
            <div
                class="p-6 rounded-[2rem] mb-8 flex items-center justify-between shadow-sm border 
                {{ ($analysis->final_result['summary_color'] ?? '') == 'warning'
                    ? 'bg-orange-50 border-orange-200 text-orange-800'
                    : (($analysis->final_result['summary_color'] ?? '') == 'danger'
                        ? 'bg-red-50 border-red-200 text-red-800'
                        : 'bg-emerald-50 border-emerald-200 text-emerald-800') }}">

                <div class="flex items-center gap-5">
                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md
                        {{ ($analysis->final_result['summary_color'] ?? '') == 'warning'
                            ? 'bg-orange-500'
                            : (($analysis->final_result['summary_color'] ?? '') == 'danger'
                                ? 'bg-red-500'
                                : 'bg-emerald-500') }}">
                        <i
                            class="fa-solid {{ ($analysis->final_result['summary_label'] ?? '') == 'Aman' ? 'fa-check-double' : 'fa-triangle-exclamation' }}"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-2xl tracking-tight">
                            HASIL: {{ $analysis->final_result['summary_label'] ?? 'Unknown' }}
                        </h2>
                        <p class="text-xs opacity-70 font-medium">ID Analisis: #VRD-{{ $analysis->id }} • Dibuat:
                            {{ $analysis->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
                <button onclick="window.print()"
                    class="bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 px-6 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Unduh Laporan
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white p-5 rounded-[2.5rem] border border-slate-100 shadow-sm">
                        <p class="text-[10px] uppercase font-extrabold text-slate-400 mb-4 px-4 tracking-widest italic">
                            Visual Analysis Viewport</p>

                        <div class="relative overflow-hidden rounded-3xl bg-slate-50 border border-slate-100">
                            <img id="mainViewport" src="{{ asset('storage/' . $analysis->s3_path) }}"
                                class="w-full transition-all duration-700" alt="Analyzed Image">
                        </div>

                        <div class="flex gap-2 mt-5 px-2">
                            <button onclick="switchView('original')" id="btn-original"
                                class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-[10px] font-bold transition shadow-sm">Original</button>
                            <button onclick="switchView('ela')" id="btn-ela"
                                class="px-5 py-2.5 bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-xl text-[10px] font-bold transition">ELA
                                Map</button>
                            <button onclick="switchView('noise')" id="btn-noise"
                                class="px-5 py-2.5 bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-xl text-[10px] font-bold transition">Noise
                                Map</button>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
                        <h4 class="font-bold mb-8 text-blue-600 italic flex items-center gap-2">
                            <i class="fa-solid fa-chart-simple"></i> Forensic Metrics
                        </h4>

                        <div class="space-y-8">
                            <div>
                                <div class="flex justify-between text-[11px] mb-3 font-bold uppercase tracking-wide">
                                    <span class="text-slate-500">Error Level (ELA)</span>
                                    <span
                                        class="{{ $analysis->ela_score > 50 ? 'text-red-600' : 'text-emerald-600' }} font-mono text-sm">
                                        {{ $analysis->ela_score }}%
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-blue-600 h-full rounded-full transition-all duration-1000 shadow-sm"
                                        style="width: {{ $analysis->ela_score }}%"></div>
                                </div>
                            </div>

                            <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] text-slate-400 uppercase font-extrabold mb-2 tracking-widest">
                                    Metadata Scan</p>
                                <p class="text-xs font-semibold text-slate-700 italic">
                                    {{ $analysis->metadata_details['summary']['status'] ?? 'No Metadata Found' }}
                                </p>
                            </div>

                            <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] text-slate-400 uppercase font-extrabold mb-2 tracking-widest">
                                    Noise Analysis</p>
                                <p class="text-xs font-semibold text-orange-600 italic">
                                    {{ $analysis->noise_status ?? 'Normal / No Issues' }}
                                </p>
                            </div>

                            <div
                                class="flex justify-between items-center p-5 bg-blue-50 rounded-2xl border border-blue-100">
                                <span
                                    class="text-[10px] text-blue-600 uppercase font-extrabold tracking-widest">Deepfake
                                    Detect</span>
                                <span
                                    class="text-sm font-black {{ $analysis->is_deepfake ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $analysis->is_deepfake ? 'POSITIVE' : 'NEGATIVE' }}
                                </span>
                            </div>
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

            // Reset all buttons to White Style
            Object.values(btns).forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
                btn.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200');
            });

            // Set active button style and change image
            if (type === 'original') {
                imgElement.src = "{{ asset('storage/' . $analysis->s3_path) }}";
                btns.original.classList.replace('bg-white', 'bg-blue-600');
                btns.original.classList.add('text-white', 'shadow-sm');
            } else if (type === 'ela') {
                imgElement.src = "{{ asset('storage/results/' . auth()->id() . '/ela_result.jpg') }}";
                btns.ela.classList.replace('bg-white', 'bg-blue-600');
                btns.ela.classList.add('text-white', 'shadow-sm');
            } else if (type === 'noise') {
                imgElement.src = "{{ asset('storage/results/' . auth()->id() . '/temp_noise_map.png') }}";
                btns.noise.classList.replace('bg-white', 'bg-blue-600');
                btns.noise.classList.add('text-white', 'shadow-sm');
            }
        }
    </script>
</body>

</html>
