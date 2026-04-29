@extends('layouts.user')

@section('title', 'User Dashboard')

@section('content')
    <style>
        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }

        .scanner-line {
            height: 2px;
            background: #3b82f6;
            position: absolute;
            width: 100%;
            box-shadow: 0 0 15px #3b82f6;
            animation: scan 2s linear infinite;
        }
    </style>

    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Mulai <span class="text-blue-500 italic">Analisis Forensik</span></h1>
            <p class="text-slate-400">Unggah foto profil atau dokumen untuk verifikasi keaslian digital.</p>
        </div>

        {{-- Loading Modal --}}
        <div id="loadingModal"
            class="fixed inset-0 bg-slate-950/90 backdrop-blur-md z-[100] hidden flex items-center justify-center">
            <div class="text-center">
                <div class="relative w-32 h-32 mx-auto mb-6">
                    <div class="absolute inset-0 border-2 border-blue-500/30 rounded-2xl overflow-hidden">
                        <div class="scanner-line"></div>
                    </div>
                    <i class="fa-solid fa-microscope text-5xl text-blue-500 absolute inset-0 m-auto h-fit"></i>
                </div>
                <h2 class="text-2xl font-bold italic mb-2">Analyzing <span class="text-blue-500">Evidence...</span></h2>
                <p class="text-slate-400 text-sm animate-pulse">Menjalankan 4-Layer Forensic Engine</p>
                <div class="mt-8 space-y-2 text-[10px] text-left max-w-xs mx-auto font-mono text-slate-500">
                    <p id="status1" class="hidden">>> Checking ELA Levels...</p>
                    <p id="status2" class="hidden">>> Extracting Metadata...</p>
                    <p id="status3" class="hidden">>> Running AI Deepfake Detector...</p>
                </div>
            </div>
        </div>

        <form id="uploadForm" action="{{ route('audit.analyze') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="bg-slate-900 border-2 border-dashed border-slate-700 rounded-[2.5rem] p-12 text-center hover:border-blue-500/50 transition">
                <input type="file" name="image" class="hidden" id="fileInput" onchange="previewImage(this)">

                {{-- Image Preview Container --}}
                <div id="previewContainer" class="hidden mb-6">
                    <div class="relative inline-block">
                        <img id="imagePreview" src="#"
                            class="max-h-64 mx-auto rounded-2xl border border-slate-700 shadow-2xl">
                        {{-- Tombol Hapus (Floating X) --}}
                        <button type="button" onclick="removeImage()" 
                            class="absolute -top-3 -right-3 bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <p id="fileName" class="text-xs text-blue-400 mt-2 italic font-bold"></p>
                </div>

                {{-- Default Dropzone Content --}}
                <div id="dropzoneContent">
                    <i class="fa-solid fa-cloud-arrow-up text-5xl text-slate-700 mb-4"></i>
                    <h3 class="text-xl font-bold">Pilih Dokumen Foto</h3>
                </div>

                <div class="mt-6 flex flex-col gap-3 items-center">
                    <div class="flex gap-2">
                        {{-- Tombol Pilih/Ganti Foto --}}
                        <button type="button" onclick="document.getElementById('fileInput').click()"
                            class="bg-slate-800 hover:bg-slate-700 px-8 py-3 rounded-2xl font-bold transition">
                            <span id="btnText">Pilih Foto</span>
                        </button>

                        {{-- Tombol Batal/Hapus (Secondary) --}}
                        <button type="button" id="removeBtn" onclick="removeImage()"
                            class="hidden bg-rose-900/30 hover:bg-rose-900/50 text-rose-500 px-6 py-3 rounded-2xl font-bold border border-rose-500/20 transition">
                            Hapus
                        </button>
                    </div>

                    {{-- Tombol Submit --}}
                    <button type="submit" id="submitBtn"
                        class="hidden bg-blue-600 hover:bg-blue-700 px-12 py-4 rounded-2xl font-bold shadow-lg shadow-blue-600/30 transition-all scale-105 active:scale-95">
                        Mulai Analisis <i class="fa-solid fa-magnifying-glass-chart ml-2"></i>
                    </button>
                </div>
            </div>
        </form>

        {{-- Layers Info --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12">
            @php
                $layers = [
                    ['L1', 'Error Level Analysis'],
                    ['L2', 'Metadata Extraction'],
                    ['L3', 'Noise Analysis'],
                    ['L4', 'AI Detection']
                ];
            @endphp
            @foreach($layers as $index => $layer)
                <div class="p-4 bg-slate-900/50 border border-slate-800 rounded-2xl text-center">
                    <div class="text-blue-500 font-bold text-[10px] uppercase tracking-widest italic">Layer {{ $index + 1 }}</div>
                    <div class="text-[9px] text-slate-500">{{ $layer[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('previewContainer').classList.remove('hidden');
                    document.getElementById('dropzoneContent').classList.add('hidden');
                    
                    document.getElementById('submitBtn').classList.remove('hidden');
                    document.getElementById('removeBtn').classList.remove('hidden');
                    
                    document.getElementById('btnText').textContent = 'Ganti Foto';
                    document.getElementById('fileName').textContent = 'Ready: ' + input.files[0].name;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage() {
            // Reset input file
            document.getElementById('fileInput').value = "";
            
            // Sembunyikan preview dan tombol aksi
            document.getElementById('previewContainer').classList.add('hidden');
            document.getElementById('submitBtn').classList.add('hidden');
            document.getElementById('removeBtn').classList.add('hidden');
            
            // Munculkan kembali dropzone asal
            document.getElementById('dropzoneContent').classList.remove('hidden');
            
            // Kembalikan teks tombol
            document.getElementById('btnText').textContent = 'Pilih Foto';
            document.getElementById('imagePreview').src = "#";
        }

        document.getElementById('uploadForm').onsubmit = function() {
            document.getElementById('loadingModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('status1').classList.remove('hidden'), 500);
            setTimeout(() => document.getElementById('status2').classList.remove('hidden'), 1500);
            setTimeout(() => document.getElementById('status3').classList.remove('hidden'), 2500);
        };
    </script>
@endsection