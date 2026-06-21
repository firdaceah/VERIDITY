@extends('layouts.user')

@section('title', 'Profil')

@section('content')
    <div class="max-w-3xl mx-auto text-slate-100">
        <div class="mb-8">
            <h1 class="text-3xl font-bold italic">Profil <span class="text-[#39D2DD]">Pengguna</span></h1>
            <p class="text-slate-400 text-sm mt-1">Kelola informasi akun website VERIDITY.</p>
        </div>

        @if (session('success'))
            <div class="mb-5 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-2xl text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-2xl text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data"
            class="bg-[#0E0E20] border border-white/10 rounded-[2rem] p-6 space-y-6">
            @csrf

            <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-3xl bg-[#39D2DD]/10 border border-[#39D2DD]/20 flex items-center justify-center overflow-hidden">
                    @if ($user->profile_photo_url)
                        <img src="{{ $user->profile_photo_url }}" class="w-full h-full object-cover" alt="{{ $user->name }}">
                    @else
                        <i class="fa-solid fa-user text-3xl text-[#39D2DD]"></i>
                    @endif
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Foto Profil</label>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        class="block w-full text-sm text-slate-300 file:mr-4 file:rounded-xl file:border-0 file:bg-[#4338CA] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                    <p class="mt-2 text-xs text-slate-500">Maksimal 4 MB. Format JPG, JPEG, atau PNG.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm text-slate-300 mb-2">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-[#111028] border border-white/10 text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#4338CA]">
            </div>

            <div>
                <label class="block text-sm text-slate-300 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-[#111028] border border-white/10 text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#4338CA]">
            </div>

            <button type="submit" class="w-full bg-[#4338CA] hover:bg-[#39D2DD] text-white font-bold py-3 rounded-xl transition">
                Simpan Profil
            </button>
        </form>
    </div>
@endsection
