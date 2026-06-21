@extends('layouts.auth')

@section('title', 'Register')

@section('auth-form')
    <div class="bg-[#1D143E]/40 backdrop-blur-xl p-8 md:p-10 rounded-3xl border border-white/10 shadow-2xl">
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
                    class="w-full px-4 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] outline-none transition text-sm"
                    placeholder="Masukkan nama Anda">
            </div>

            <div class="md:col-span-2 text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] outline-none transition text-sm"
                    placeholder="nama@student.pens.ac.id">
            </div>

            <div class="text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                <div class="relative">
                    <input id="registerPassword" type="password" name="password" required
                        class="w-full px-4 pr-12 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('registerPassword', 'registerPasswordIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-[#39D2DD]">
                        <i id="registerPasswordIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="text-left">
                <label class="block text-sm font-medium text-slate-300 mb-2">Konfirmasi</label>
                <div class="relative">
                    <input id="registerPasswordConfirmation" type="password" name="password_confirmation" required
                        class="w-full px-4 pr-12 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('registerPasswordConfirmation', 'registerPasswordConfirmationIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-[#39D2DD]">
                        <i id="registerPasswordConfirmationIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <label class="md:col-span-2 flex items-start gap-3 text-left text-xs text-slate-400 leading-relaxed">
                <input type="checkbox" name="privacy_policy" required
                    class="mt-1 h-4 w-4 rounded border-white/20 bg-[#0E0E20] text-[#4338CA] focus:ring-[#39D2DD]">
                <span>
                    Saya telah membaca dan menyetujui
                    <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener"
                        class="text-[#39D2DD] font-bold hover:underline">Kebijakan Privasi</a>
                    VERIDITY.
                </span>
            </label>

            <div class="md:col-span-2 mt-2">
                <button type="submit"
                    class="w-full bg-[#4338CA] hover:bg-[#372FA8] py-4 rounded-xl font-bold transition shadow-lg shadow-[#4338CA]/20">
                    Daftar Sekarang
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-sm text-slate-400">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-[#39D2DD] font-bold hover:underline">Masuk di sini</a>
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
