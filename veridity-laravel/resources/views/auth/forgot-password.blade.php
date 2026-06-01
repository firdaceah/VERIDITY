@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('auth-form')
    <div class="bg-slate-800/40 backdrop-blur-xl p-8 rounded-3xl border border-slate-700 shadow-2xl">
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
                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                    placeholder="nama@student.pens.ac.id">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 py-3 rounded-xl font-bold transition shadow-lg shadow-blue-600/20">
                Buat Token Reset
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" class="text-blue-400 font-bold hover:underline">Kembali ke login</a>
        </div>
    </div>
@endsection
