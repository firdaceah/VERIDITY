@extends('layouts.admin')

@section('title', 'Fraud Repository')

@section('content')
    <div class="mb-8 text-slate-100">
        <h1 class="text-3xl font-bold italic">Fraud <span class="text-red-500">Repository</span></h1>
        <p class="text-slate-400 text-sm mt-1">Koleksi berkas citra gambar termanipulasi yang ditemukan oleh sistem.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold border-b border-slate-800/40">
                    <tr>
                        <th class="px-6 py-4">Evidence</th>
                        <th class="px-6 py-4">Detection Info</th>
                        <th class="px-6 py-4">Reported By</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($fraudCases as $case)
                        <tr class="hover:bg-red-500/[0.02] transition duration-150">
                            {{-- Bukti Visual Gambar --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <img src="{{ asset('storage/' . $case->s3_path) }}" class="w-14 h-14 object-cover rounded-2xl border border-slate-800 shadow-md">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-slate-200 text-sm truncate max-w-[180px]" title="{{ $case->image_name }}">
                                            {{ Str::limit($case->image_name, 25) }}
                                        </span>
                                        <span class="text-[9px] text-slate-500 font-mono">ID: #VRD-{{ $case->id }}</span>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Info Deteksi Tingkat Kerawanan --}}
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 font-mono">
                                    <span class="text-red-400 font-bold">ELA SCORE: {{ number_format($case->ela_score, 2) }}%</span>
                                    <span class="text-orange-400 text-[10px] italic max-w-[220px] truncate" title="{{ $case->noise_status }}">
                                        {{ $case->noise_status }}
                                    </span>
                                </div>
                            </td>
                            
                            {{-- Pengunggah Berkas --}}
                            <td class="px-6 py-4 font-bold text-blue-400">
                                {{ $case->user->name ?? 'System/Guest' }}
                            </td>
                            
                            {{-- Menu Aksi Interaktif (Mendukung Konfirmasi Hapus JavaScript/SweetAlert2) --}}
                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ route('admin.audit.show', $case->id) }}" 
                                       class="w-8 h-8 flex items-center justify-center bg-slate-800 hover:bg-slate-700 text-blue-400 rounded-xl transition focus:outline-none"
                                       title="Buka Lembar Hasil Kompilasi">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                    
                                    <form id="delete-form-{{ $case->id }}" action="{{ route('audit.destroy', $case->id) }}" method="POST" class="inline">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="button" onclick="confirmFraudDelete({{ $case->id }}, '{{ $case->image_name }}')"
                                                class="w-8 h-8 flex items-center justify-center bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white rounded-xl transition focus:outline-none"
                                                title="Hapus Barang Bukti">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-20 text-center text-slate-500 italic border-none">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fa-solid fa-shield-cat text-2xl opacity-30 text-emerald-400"></i>
                                    <p class="text-sm">Repository is clean. Tidak ditemukan berkas fraud manipulasi berbahaya.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sistem Pemicu Modal Dialog Konfirmasi Hapus Khusus Admin --}}
    <script>
        function confirmFraudDelete(id, filename) {
            // Memanfaatkan SweetAlert2 yang sudah terintegrasi di sidebar layouts admin kamu
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Bukti Fraud?',
                    html: `Apakah Anda yakin ingin menghapus barang bukti rekayasa <br><span class="text-red-400 font-mono text-[11px]">${filename}</span>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#334155',
                    confirmButtonText: 'Ya, Hapus Bukti!',
                    cancelButtonText: 'Batal',
                    background: '#0f172a',
                    color: '#ffffff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            } else {
                // Fail-safe fallback jika SweetAlert terhambat asset loading-nya
                if (confirm(`Hapus permanen bukti fraud berkas: ${filename}?`)) {
                    document.getElementById('delete-form-' + id).submit();
                }
            }
        }
    </script>
@endsection