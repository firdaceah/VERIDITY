@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('auth-form')
    <div class="bg-[#1D143E]/40 backdrop-blur-xl p-8 rounded-3xl border border-white/10 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-2">Lupa Password</h2>
        <p class="text-sm text-slate-400 mb-6">Masukkan email akun untuk membuat token reset password.</p>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/50 p-4 rounded-2xl mb-6 text-xs text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] focus:border-transparent outline-none transition text-sm"
                    placeholder="nama@student.pens.ac.id">
            </div>

            <button type="submit"
                class="w-full bg-[#4338CA] hover:bg-[#372FA8] py-3 rounded-xl font-bold transition shadow-lg shadow-[#4338CA]/20">
                Buat Token Reset
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" class="text-[#39D2DD] font-bold hover:underline">Kembali ke login</a>
        </div>
    </div>
@endsection
