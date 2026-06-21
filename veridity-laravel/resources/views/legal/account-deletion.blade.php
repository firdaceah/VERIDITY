@extends('layouts.app')

@section('title', 'Penghapusan Akun')

@section('content')
    <section class="bg-[#111028] py-16 md:py-24">
        <div class="container mx-auto px-6 max-w-4xl bg-[#0E0E20]/70 border border-white/10 rounded-[2rem] p-6 md:p-10 shadow-2xl">
            <p class="text-[#39D2DD] text-sm font-bold uppercase tracking-[0.25em] mb-4">VERIDITY</p>
            <h1 class="text-4xl md:text-5xl font-black italic mb-4">Penghapusan <span class="text-[#39D2DD]">Akun</span></h1>
            <p class="text-slate-400 mb-10">Halaman ini menjelaskan cara meminta penghapusan akun dan data terkait.</p>

            <div class="space-y-8 text-slate-300 leading-relaxed">
                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Cara Mengajukan Penghapusan Akun</h2>
                    <ol class="list-decimal pl-6 space-y-2">
                        <li>Kirim email ke <a href="mailto:firdarahayu105@gmail.com" class="text-[#39D2DD] font-bold hover:underline">firdarahayu105@gmail.com</a>.</li>
                        <li>Gunakan subjek email: <span class="font-bold text-white">Permintaan Hapus Akun VERIDITY</span>.</li>
                        <li>Sertakan email akun VERIDITY yang ingin dihapus.</li>
                        <li>Tim VERIDITY akan memverifikasi permintaan dan memproses penghapusan akun.</li>
                    </ol>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Data yang Dihapus</h2>
                    <p>
                        Penghapusan akun mencakup data profil seperti nama, email, foto profil, token akses, riwayat
                        analisis, file hasil analisis, dan laporan PDF yang tersimpan pada sistem VERIDITY.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Data yang Dapat Disimpan Sementara</h2>
                    <p>
                        Catatan teknis seperti log server atau catatan keamanan dapat tersimpan sementara hingga 90 hari
                        untuk kebutuhan keamanan, audit teknis, dan pencegahan penyalahgunaan. Setelah periode tersebut,
                        data teknis akan dihapus atau dianonimkan sesuai kebutuhan sistem.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">Penghapusan Sebagian Data</h2>
                    <p>
                        Jika tidak ingin menghapus akun sepenuhnya, pengguna dapat menghapus riwayat analisis tertentu
                        dari aplikasi atau mengikuti panduan pada halaman
                        <a href="{{ route('data-deletion') }}" class="text-[#39D2DD] font-bold hover:underline">Penghapusan Data</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
