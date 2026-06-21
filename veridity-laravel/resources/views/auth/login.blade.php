@extends('layouts.auth')

@section('title', 'Login')

@section('auth-form')
    <div class="bg-[#1D143E]/40 backdrop-blur-xl p-8 rounded-3xl border border-white/10 shadow-2xl">
        @if (session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/50 p-4 rounded-2xl mb-6 flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5"></i>
                <div>
                    <h5 class="text-sm font-bold text-emerald-400 leading-none mb-1">Berhasil!</h5>
                    <p class="text-xs text-emerald-500/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/50 p-4 rounded-2xl mb-6 flex items-start gap-3">
                <i class="fa-solid fa-circle-xmark text-red-500 mt-0.5"></i>
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
                        class="w-full pl-10 pr-4 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] focus:border-transparent outline-none transition text-sm"
                        placeholder="nama@student.pens.ac.id">
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-300 mb-2 block">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input id="loginPassword" type="password" name="password" required
                        class="w-full pl-10 pr-12 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] focus:border-transparent outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('loginPassword', 'loginPasswordIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-[#39D2DD]">
                        <i id="loginPasswordIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('password.request') }}" class="text-sm text-[#39D2DD] hover:underline font-semibold">
                    Lupa password?
                </a>
            </div>

            <button type="submit"
                class="w-full bg-[#4338CA] hover:bg-[#372FA8] py-3 rounded-xl font-bold transition shadow-lg shadow-[#4338CA]/20 flex items-center justify-center gap-2">
                Masuk Sekarang <i class="fa-solid fa-right-to-bracket"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/10/50 text-center text-sm">
            <span class="text-slate-400">Belum punya akun?</span>
            <a href="{{ route('register') }}" class="text-[#39D2DD] font-bold ml-1 hover:underline">Daftar Gratis</a>
        </div>
    </div>

<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }
</script>
@endsection
