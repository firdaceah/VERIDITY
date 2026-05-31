@extends('layouts.user')

@section('title', 'Riwayat Audit')

@section('content')
    {{-- Inisialisasi State Tab Menggunakan Alpine.js --}}
    <div x-data="{ activeTab: 'images' }" class="text-slate-100">
        
        {{-- Header Section --}}
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold italic">My <span class="text-blue-500">Audits</span></h1>
                <p class="text-slate-400 text-sm mt-1">Daftar rekaman riwayat investigasi forensik digital milik Anda.</p>
            </div>

            {{-- Sakelar Navigasi Tab --}}
            <div class="flex bg-slate-950 p-1 rounded-2xl border border-slate-800/80 self-start">
                <button @click="activeTab = 'images'" 
                    :class="activeTab === 'images' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-200'"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-image"></i> Citra Gambar
                </button>
                <button @click="activeTab = 'documents'" 
                    :class="activeTab === 'documents' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:text-slate-200'"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-file-lines"></i> Dokumen Teks
                </button>
            </div>
        </div>

        @php
            // Pisahkan data di level Blade untuk performa rendering optimal
            $imageAudits = $myAudits->filter(function($audit) {
                $ext = strtolower(pathinfo($audit->image_name, PATHINFO_EXTENSION));
                return in_array($ext, ['jpg', 'jpeg', 'png']);
            });

            $documentAudits = $myAudits->filter(function($audit) {
                $ext = strtolower(pathinfo($audit->image_name, PATHINFO_EXTENSION));
                return in_array($ext, ['pdf', 'docx']);
            });
        @endphp

        {{-- ========================================================================= --}}
        {{-- TAB A: KELOMPOK CITRA GAMBAR --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'images'" x-transition:enter="transition ease-out duration-300" class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @forelse($imageAudits as $audit)
                <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden group hover:border-blue-500/50 transition-all duration-300 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="aspect-video bg-slate-950 relative overflow-hidden border-b border-slate-800/40">
                            <img src="{{ asset('storage/' . $audit->s3_path) }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                alt="{{ $audit->image_name }}">

                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wider shadow-2xl text-white
                                    {{ ($audit->final_result['summary_color'] ?? '') == 'success' ? 'bg-emerald-600 border border-emerald-500' : (($audit->final_result['summary_color'] ?? '') == 'warning' ? 'bg-orange-500 border border-orange-400' : 'bg-red-600 border border-red-500') }}">
                                    {{ $audit->final_result['summary_label'] ?? 'PROSES' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            <h4 class="font-bold text-slate-200 mb-1 truncate text-base" title="{{ $audit->image_name }}">
                                {{ $audit->image_name }}
                            </h4>
                            <p class="text-[10px] text-slate-500 italic flex items-center gap-1">
                                <i class="fa-regular fa-clock text-[9px]"></i>
                                {{ $audit->created_at->format('d M Y, H:i') }} WIB
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <div class="flex justify-between items-center pt-4 border-t border-slate-800/60">
                            <div class="flex flex-col">
                                <span class="text-[9px] uppercase text-slate-500 font-bold tracking-wider">Maks Deviasi ELA</span>
                                <span class="text-xs font-mono {{ $audit->ela_score > 15 ? 'text-red-400' : 'text-emerald-400' }} font-bold">
                                    {{ number_format($audit->ela_score, 2) }}%
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <form id="delete-form-{{ $audit->id }}" action="{{ route('audit.destroy', $audit->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete({{ $audit->id }}, '{{ $audit->image_name }}')"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all duration-200 focus:outline-none">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                                <a href="{{ route('user.result', $audit->id) }}"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition-all duration-150 shadow-md">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-slate-900/40 rounded-[3rem] border-2 border-dashed border-slate-800/80">
                    <div class="w-14 h-14 bg-slate-800/40 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-600 text-xl">
                        <i class="fa-solid fa-image"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-sm">Belum Ada Riwayat Citra Gambar</p>
                </div>
            @endforelse
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB B: KELOMPOK DOKUMEN TEKS (PDF / DOCX) --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'documents'" x-transition:enter="transition ease-out duration-300" class="grid grid-cols-1 md:grid-cols-3 gap-8" x-cloak>
            @forelse($documentAudits as $audit)
                @php
                    $ext = strtolower(pathinfo($audit->image_name, PATHINFO_EXTENSION));
                @endphp
                <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden group hover:border-blue-500/50 transition-all duration-300 shadow-xl flex flex-col justify-between">
                    <div>
                        {{-- Tampilan Header Kartu Khusus File Dokumen --}}
                        <div class="aspect-video bg-gradient-to-br from-slate-950 to-slate-900 relative overflow-hidden border-b border-slate-800/40 flex items-center justify-center p-6">
                            <div class="w-16 h-16 rounded-2xl {{ $ext == 'pdf' ? 'bg-red-500/10 text-red-400' : 'bg-blue-500/10 text-blue-400' }} flex items-center justify-center text-3xl group-hover:scale-110 transition-transform duration-500 shadow-inner">
                                <i class="fa-solid {{ $ext == 'pdf' ? 'fa-file-pdf' : 'fa-file-word' }}"></i>
                            </div>

                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wider shadow-2xl text-white
                                    {{ ($audit->final_result['summary_color'] ?? '') == 'success' ? 'bg-emerald-600 border border-emerald-500' : (($audit->final_result['summary_color'] ?? '') == 'warning' ? 'bg-orange-500 border border-orange-400' : 'bg-red-600 border border-red-500') }}">
                                    {{ $audit->final_result['summary_label'] ?? 'PROSES' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            <h4 class="font-bold text-slate-200 mb-1 truncate text-base" title="{{ $audit->image_name }}">
                                {{ $audit->image_name }}
                            </h4>
                            <p class="text-[10px] text-slate-500 italic flex items-center gap-1">
                                <i class="fa-regular fa-clock text-[9px]"></i>
                                {{ $audit->created_at->format('d M Y, H:i') }} WIB
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <div class="flex justify-between items-center pt-4 border-t border-slate-800/60">
                            <div class="flex flex-col">
                                <span class="text-[9px] uppercase text-slate-500 font-bold tracking-wider">Tipe Dokumen</span>
                                <span class="text-xs font-mono font-bold uppercase tracking-wider text-blue-400">
                                    {{ $ext }} File
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <form id="delete-form-{{ $audit->id }}" action="{{ route('audit.destroy', $audit->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete({{ $audit->id }}, '{{ $audit->image_name }}')"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all duration-200 focus:outline-none">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                                <a href="{{ route('user.result', $audit->id) }}"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition-all duration-150 shadow-md">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-slate-900/40 rounded-[3rem] border-2 border-dashed border-slate-800/80">
                    <div class="w-14 h-14 bg-slate-800/40 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-600 text-xl">
                        <i class="fa-solid fa-file-circle-exclamation"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-sm">Belum Ada Riwayat Dokumen Teks</p>
                </div>
            @endforelse
        </div>

    </div>

    {{-- Penyelamat CSS x-cloak Alpine.js --}}
    <style>[x-cloak] { display: none !important; }</style>

    {{-- Sistem Script Modal Dialog SweetAlert2 --}}
    <script>
        function confirmDelete(id, filename) {
            Swal.fire({
                title: 'Hapus Riwayat?',
                html: `Apakah Anda yakin ingin menghapus berkas <br><span class="text-red-400 font-mono text-[11px]">${filename}</span> dari database?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', 
                cancelButtonColor: '#334155',  
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#0f172a',         
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            })
        }

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                background: '#0f172a',
                color: '#ffffff',
                confirmButtonColor: '#3b82f6',
                timer: 2500
            });
        @endif
    </script>
@endsection