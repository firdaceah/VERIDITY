@extends('layouts.auth')

@section('title', 'Reset Password')

@section('auth-form')
    <div class="bg-[#1D143E]/40 backdrop-blur-xl p-8 rounded-3xl border border-white/10 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-2">Reset Password</h2>
        <p class="text-sm text-slate-400 mb-6">Gunakan token reset untuk membuat password baru.</p>

        @if (session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/50 p-4 rounded-2xl mb-6 text-xs text-emerald-300">
                <p>{{ session('success') }}</p>
                @if (session('dev_reset_token'))
                    <p class="mt-2 font-mono break-all text-emerald-200">{{ session('dev_reset_token') }}</p>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/50 p-4 rounded-2xl mb-6 text-xs text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <input type="email" name="email" value="{{ old('email', session('reset_email')) }}" required
                    class="w-full px-4 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] focus:border-transparent outline-none transition text-sm"
                    placeholder="nama@student.pens.ac.id">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Token Reset</label>
                <input type="text" name="token" value="{{ old('token', session('dev_reset_token')) }}" required
                    class="w-full px-4 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] focus:border-transparent outline-none transition text-sm font-mono"
                    placeholder="tempel token reset">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Password Baru</label>
                <div class="relative">
                    <input id="resetPassword" type="password" name="password" required
                        class="w-full px-4 pr-12 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('resetPassword', 'resetPasswordIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-[#39D2DD]">
                        <i id="resetPasswordIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Konfirmasi Password</label>
                <div class="relative">
                    <input id="resetPasswordConfirmation" type="password" name="password_confirmation" required
                        class="w-full px-4 pr-12 py-3 bg-[#0E0E20]/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-[#39D2DD] outline-none transition text-sm"
                        placeholder="password">
                    <button type="button" onclick="togglePassword('resetPasswordConfirmation', 'resetPasswordConfirmationIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-[#39D2DD]">
                        <i id="resetPasswordConfirmationIcon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-[#4338CA] hover:bg-[#372FA8] py-3 rounded-xl font-bold transition shadow-lg shadow-[#4338CA]/20">
                Reset Password
            </button>
        </form>
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
