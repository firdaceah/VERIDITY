@extends('layouts.user')

@section('title', 'Riwayat Audit')

@section('content')
    <div class="mb-10 text-slate-100">
        <h1 class="text-3xl font-bold italic">My <span class="text-blue-500">Audits</span></h1>
        <p class="text-slate-400">Daftar riwayat analisis forensik citra digital yang telah Anda lakukan.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @forelse($myAudits as $audit)
            <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden group hover:border-blue-500/50 transition-all duration-300 shadow-xl flex flex-col justify-between">
                <div>
                    {{-- Preview Image Viewport --}}
                    <div class="aspect-video bg-slate-950 relative overflow-hidden border-b border-slate-800/40">
                        <img src="{{ asset('storage/' . $audit->s3_path) }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            alt="{{ $audit->image_name }}">

                        {{-- REVISI PERBAIKAN BADGE: Menggunakan warna solid penuh agar terbaca sangat jelas --}}
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wider shadow-2xl text-white
                                {{ ($audit->final_result['summary_color'] ?? '') == 'success' 
                                    ? 'bg-emerald-600 border border-emerald-500' 
                                    : (($audit->final_result['summary_color'] ?? '') == 'warning' 
                                        ? 'bg-orange-500 border border-orange-400' 
                                        : 'bg-red-600 border border-red-500') }}">
                                {{ $audit->final_result['summary_label'] ?? 'PROSES AUDIT' }}
                            </span>
                        </div>
                    </div>

                    {{-- Konten Informasi Berkas --}}
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

                {{-- Action Row Bottom Menu --}}
                <div class="px-6 pb-6">
                    <div class="flex justify-between items-center pt-4 border-t border-slate-800/60">
                        <div class="flex flex-col">
                            <span class="text-[9px] uppercase text-slate-500 font-bold tracking-wider">Maks Deviasi ELA</span>
                            <span class="text-xs font-mono {{ $audit->ela_score > 15 ? 'text-red-400' : 'text-emerald-400' }} font-bold">
                                {{ number_format($audit->ela_score, 2) }}%
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            {{-- Form Action Hapus Riwayat --}}
                            <form id="delete-form-{{ $audit->id }}" action="{{ route('audit.destroy', $audit->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmDelete({{ $audit->id }}, '{{ $audit->image_name }}')"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all duration-200 focus:outline-none"
                                    title="Hapus Dokumen">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </form>

                            {{-- Tombol Detail Hasil Forensik --}}
                            <a href="{{ route('user.result', $audit->id) }}"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition-all duration-150 shadow-md shadow-blue-600/10 focus:outline-none">
                                Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            {{-- Tampilan Placeholder jika tabel kosong --}}
            <div class="col-span-full py-24 text-center bg-slate-900/40 rounded-[3rem] border-2 border-dashed border-slate-800/80">
                <div class="w-16 h-16 bg-slate-800/40 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-600 text-2xl">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <p class="text-slate-400 font-bold text-base">Belum Ada Riwayat Investigasi</p>
                <p class="text-slate-600 text-xs mt-1">Silakan menuju halaman utama untuk mulai memindai keaslian citra gambar digital.</p>
            </div>
        @endforelse
    </div>

    {{-- Sistem Pemicu Modal Dialog SweetAlert2 --}}
    <script>
        // 1. Notifikasi Interseptor Konfirmasi Hapus Data Berkas Audit
        function confirmDelete(id, filename) {
            Swal.fire({
                title: 'Hapus Riwayat Citra?',
                html: `Apakah Anda yakin ingin menghapus berkas <br><span class="text-red-400 font-mono text-[11px]">${filename}</span> dari server?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', 
                cancelButtonColor: '#334155',  
                confirmButtonText: 'Ya, Hapus Permanen!',
                cancelButtonText: 'Batal',
                background: '#0f172a',         
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            })
        }

        // 2. Tembakan Berhasil (Trigger otomatis pasca redirect session back)
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Aksi Berhasil!',
                text: "{{ session('success') }}",
                background: '#0f172a',
                color: '#ffffff',
                confirmButtonColor: '#3b82f6',
                timer: 2500
            });
        @endif
    </script>
@endsection