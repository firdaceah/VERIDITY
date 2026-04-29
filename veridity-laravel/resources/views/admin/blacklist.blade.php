@extends('layouts.admin')

@section('title', 'Fraud Repository')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold italic">Fraud <span class="text-red-500">Repository</span></h1>
        <p class="text-slate-400 text-sm mt-1">Koleksi gambar termanipulasi yang ditemukan oleh sistem.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-950/50 text-slate-500 uppercase text-[10px] tracking-widest font-bold">
                <tr>
                    <th class="px-6 py-4">Evidence</th>
                    <th class="px-6 py-4">Detection Info</th>
                    <th class="px-6 py-4">Reported By</th>
                    <th class="px-6 py-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-xs">
                @forelse($fraudCases as $case)
                    <tr class="hover:bg-red-500/5 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('storage/' . $case->s3_path) }}" class="w-14 h-14 object-cover rounded-xl border border-slate-700">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-200">{{ Str::limit($case->image_name, 15) }}</span>
                                    <span class="text-[9px] text-slate-500">ID: #{{ $case->id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-red-500 font-mono font-bold">ELA: {{ $case->ela_score }}%</span>
                                <span class="text-orange-400 text-[10px] italic">{{ $case->noise_status }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 italic text-blue-400">{{ $case->user->name ?? 'Guest' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('admin.audit.show', $case->id) }}" class="p-2 bg-slate-800 rounded-lg text-blue-400"><i class="fa-solid fa-eye"></i></a>
                                
                                {{-- DELETE: Menghapus bukti dari repository --}}
                                <form action="{{ route('audit.destroy', $case->id) }}" method="POST" onsubmit="return confirm('Hapus bukti ini dari database?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-500/10 rounded-lg text-red-500 hover:bg-red-500 hover:text-white transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-20 text-center text-slate-600">Repository is clean.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection