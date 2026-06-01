@extends('layouts.auth')

@section('title', 'Register')

@section('auth-form')
    <div class="bg-slate-800/40 backdrop-blur-xl p-8 md:p-10 rounded-3xl border border-slate-700 shadow-2xl">
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

        <form method="POST" action="{{ route('register') }}" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf

            <div class="md:col-span-2 text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Nama Lengkap</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"
                    placeholder="Masukkan nama Anda">
            </div>

            <div class="md:col-span-2 text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"
                    placeholder="nama@student.pens.ac.id">
            </div>

            <div class="text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                <div class="relative">
                    <input id="registerPassword" type="password" name="password" required
                        class="w-full px-4 pr-12 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('registerPassword', 'registerPasswordIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-blue-400">
                        <i id="registerPasswordIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Konfirmasi</label>
                <div class="relative">
                    <input id="registerPasswordConfirmation" type="password" name="password_confirmation" required
                        class="w-full px-4 pr-12 py-3 bg-slate-900/50 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('registerPasswordConfirmation', 'registerPasswordConfirmationIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-blue-400">
                        <i id="registerPasswordConfirmationIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="md:col-span-2 mt-2">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-xl font-bold transition shadow-lg shadow-blue-600/20">
                    Daftar Sekarang
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-sm text-slate-400">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-blue-400 font-bold hover:underline">Masuk di sini</a>
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
