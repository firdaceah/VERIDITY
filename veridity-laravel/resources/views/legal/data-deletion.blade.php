@extends('layouts.app')

@section('title', 'Penghapusan Data')

@section('content')
    <section class="bg-[#111028] py-16 md:py-24">
        <div class="container mx-auto px-6 max-w-4xl bg-[#0E0E20]/70 border border-white/10 rounded-[2rem] p-6 md:p-10 shadow-2xl">
            <p class="text-[#39D2DD] text-sm font-bold uppercase tracking-[0.25em] mb-4">VERIDITY</p>
            <h1 class="text-4xl md:text-5xl font-black italic mb-4">Penghapusan <span class="text-[#39D2DD]">Data</span></h1>
            <p class="text-slate-400 mb-10">Pengguna dapat meminta penghapusan sebagian data tanpa menghapus akun.</p>

            <div class="space-y-8 text-slate-300 leading-relaxed">
                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Data yang Dapat Dihapus</h2>
                    <p>
                        Pengguna dapat menghapus riwayat analisis, hasil analisis, laporan PDF, dan foto profil. Data
                        akun dasar seperti nama dan email tetap disimpan selama akun masih aktif agar proses login dan
                        identifikasi akun tetap berjalan.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Cara Menghapus Riwayat Analisis</h2>
                    <ol class="list-decimal pl-6 space-y-2">
                        <li>Login ke aplikasi VERIDITY.</li>
                        <li>Buka halaman Riwayat.</li>
                        <li>Pilih item analisis yang ingin dihapus.</li>
                        <li>Tekan tombol hapus pada riwayat tersebut.</li>
                    </ol>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Cara Meminta Bantuan Penghapusan Data</h2>
                    <ol class="list-decimal pl-6 space-y-2">
                        <li>Kirim email ke <a href="mailto:firdarahayu105@gmail.com" class="text-[#39D2DD] font-bold hover:underline">firdarahayu105@gmail.com</a>.</li>
                        <li>Gunakan subjek email: <span class="font-bold text-white">Permintaan Hapus Data VERIDITY</span>.</li>
                        <li>Sebutkan data yang ingin dihapus, misalnya riwayat analisis, laporan PDF, atau foto profil.</li>
                    </ol>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Retensi Data</h2>
                    <p>
                        Permintaan penghapusan data akan diproses setelah verifikasi. Catatan teknis seperti log server
                        dapat tersimpan sementara hingga 90 hari untuk keamanan dan audit teknis.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Penghapusan Akun</h2>
                    <p>
                        Jika ingin menghapus seluruh akun dan data terkait, ikuti panduan pada halaman
                        <a href="{{ route('account-deletion') }}" class="text-[#39D2DD] font-bold hover:underline">Penghapusan Akun</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
