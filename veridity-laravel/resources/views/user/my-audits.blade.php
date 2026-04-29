@extends('layouts.user')

@section('title', 'Riwayat Audit')

@section('content')
    <div class="mb-10">
        <h1 class="text-3xl font-bold italic">My <span class="text-blue-500">Audits</span></h1>
        <p class="text-slate-400">Daftar riwayat analisis forensik yang telah Anda lakukan.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @forelse($myAudits as $audit)
            <div
                class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden group hover:border-blue-500/50 transition-all shadow-xl">
                {{-- Preview Image --}}
                <div class="aspect-video bg-slate-800 relative overflow-hidden">
                    <img src="{{ asset('storage/' . $audit->s3_path) }}"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                        alt="{{ $audit->image_name }}">

                    {{-- Status Badge --}}
                    <div class="absolute top-4 right-4">
                        <span
                            class="px-3 py-1 rounded-full text-[10px] font-bold uppercase shadow-lg {{ ($audit->final_result['summary_label'] ?? 'Aman') == 'Aman' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                            {{ $audit->final_result['summary_label'] ?? 'Aman' }}
                        </span>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-6">
                    <h4 class="font-bold text-slate-200 mb-1 truncate text-lg">{{ $audit->image_name }}</h4>
                    <p class="text-[10px] text-slate-500 mb-4 italic">{{ $audit->created_at->format('d M Y, H:i') }}</p>

                    {{-- Action Row --}}
                    <div class="flex justify-between items-center pt-4 border-t border-slate-800/50">
                        <div class="flex flex-col">
                            <span class="text-[9px] uppercase text-slate-500 font-bold tracking-wider">ELA Score</span>
                            <span class="text-sm font-mono text-blue-400 font-bold">{{ $audit->ela_score }}%</span>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Delete Button --}}
                            <form id="delete-form-{{ $audit->id }}" action="{{ route('audit.destroy', $audit->id) }}"
                                method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmDelete({{ $audit->id }})"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-300">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            </form>

                            {{-- Detail Button --}}
                            <a href="{{ route('user.result', $audit->id) }}"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-blue-600/20">
                                Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="col-span-full py-24 text-center bg-slate-900/50 rounded-[3rem] border-2 border-dashed border-slate-800">
                <i class="fa-solid fa-folder-open text-5xl text-slate-700 mb-4"></i>
                <p class="text-slate-500 italic text-lg">Anda belum pernah melakukan audit.<br><span class="text-sm">Ayo
                        mulai sekarang!</span></p>
            </div>
        @endforelse
    </div>

    <script>
        // 1. Notifikasi Konfirmasi Hapus
        function confirmDelete(id) {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // warna merah tailwind
                cancelButtonColor: '#334155', // warna slate tailwind
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#0f172a', // tema dark
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            })
        }

        // 2. Notifikasi Berhasil (Muncul setelah redirect dari Controller)
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                background: '#0f172a',
                color: '#ffffff',
                confirmButtonColor: '#3b82f6',
                timer: 3000
            });
        @endif
    </script>
@endsection
