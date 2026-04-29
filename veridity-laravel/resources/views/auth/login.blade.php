@extends('layouts.auth')

@section('title', 'Login')

@section('auth-form')
    <div class="bg-slate-800/40 backdrop-blur-xl p-8 rounded-3xl border border-slate-700 shadow-2xl">
        @if (session('success'))
            <div
                class="bg-emerald-500/10 border border-emerald-500/50 p-4 rounded-2xl mb-6 flex items-start gap-3 animate-fade-in">
                <div class="text-emerald-500 mt-0.5">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-emerald-400 leading-none mb-1">Berhasil!</h5>
                    <p class="text-xs text-emerald-500/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/50 p-4 rounded-2xl mb-6 flex items-start gap-3">
                <div class="text-red-500 mt-0.5">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div class="text-xs text-red-400">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" required
                        class="w-full pl-10 pr-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                        placeholder="nama@student.pens.ac.id">
                </div>
            </div>

            <div>
                <div class="flex justify-between mb-2">
                    <label class="text-sm font-medium text-slate-300">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-blue-400 hover:underline italic">
                            Lupa Password?
                        </a>
                    @endif
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" required
                        class="w-full pl-10 pr-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
                        placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember"
                    class="rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-blue-500">
                <label for="remember" class="ml-2 text-sm text-slate-400">Ingat perangkat ini</label>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 py-3 rounded-xl font-bold transition shadow-lg shadow-blue-600/20 flex items-center justify-center gap-2">
                Masuk Sekarang <i class="fa-solid fa-right-to-bracket"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-700/50 text-center text-sm">
            <span class="text-slate-400">Belum punya akun?</span>
            <a href="{{ route('register') }}" class="text-blue-400 font-bold ml-1 hover:underline">Daftar Gratis</a>
        </div>
    </div>


@endsection
