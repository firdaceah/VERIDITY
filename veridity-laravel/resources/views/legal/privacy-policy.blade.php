@extends('layouts.app')

@section('title', 'Kebijakan Privasi')

@section('content')
    <section class="bg-[#111028] py-16 md:py-24">
        <div class="container mx-auto px-6 max-w-4xl bg-[#0E0E20]/70 border border-white/10 rounded-[2rem] p-6 md:p-10 shadow-2xl">
            <p class="text-[#39D2DD] text-sm font-bold uppercase tracking-[0.25em] mb-4">VERIDITY</p>
            <h1 class="text-4xl md:text-5xl font-black italic mb-4">Kebijakan <span class="text-[#39D2DD]">Privasi</span></h1>
            <p class="text-slate-400 mb-10">Terakhir diperbarui: 18 Juni 2026</p>

            <div class="space-y-8 text-slate-300 leading-relaxed">
                <div>
                    <h2 class="text-xl font-bold text-white mb-3">1. Informasi yang Kami Kumpulkan</h2>
                    <p>
                        VERIDITY mengumpulkan data yang diperlukan untuk menjalankan fitur autentikasi dan analisis
                        forensik digital, termasuk nama, email, password terenkripsi, foto profil jika ditambahkan,
                        file foto atau dokumen yang diunggah untuk analisis, hasil analisis, riwayat analisis, dan
                        laporan PDF yang dihasilkan.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">2. Cara Data Digunakan</h2>
                    <p>
                        Data digunakan untuk membuat dan mengamankan akun, memproses foto atau dokumen, menampilkan
                        hasil analisis, menyimpan riwayat pribadi pengguna, menghasilkan laporan PDF, memperbaiki
                        kualitas layanan, dan menjaga keamanan sistem.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">3. Penyimpanan File</h2>
                    <p>
                        File hasil analisis seperti foto original, peta ELA, noise map, foto profil, dan laporan PDF
                        dapat disimpan pada storage aplikasi agar dapat ditampilkan kembali melalui website dan aplikasi
                        mobile. Dokumen PDF yang diunggah untuk proses analisis diproses sebagai input awal dan tidak
                        dimaksudkan sebagai arsip publik.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">4. Keamanan Data</h2>
                    <p>
                        Data dikirim melalui koneksi HTTPS. Password disimpan dalam bentuk hash, bukan teks asli. Akses
                        ke data pengguna dibatasi sesuai kebutuhan sistem dan tidak dijual kepada pihak ketiga.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">5. Pembagian Data</h2>
                    <p>
                        VERIDITY tidak menjual data pengguna. Data dapat diproses oleh layanan pendukung seperti server
                        aplikasi, database, dan storage untuk menjalankan fitur analisis. Data pengguna tidak dibagikan
                        secara publik antar pengguna.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">6. Hak Pengguna</h2>
                    <p>
                        Pengguna dapat memperbarui profil, menghapus riwayat analisis tertentu, atau meminta penghapusan
                        akun dan data terkait. Panduan penghapusan tersedia pada halaman
                        <a href="{{ route('account-deletion') }}" class="text-[#39D2DD] font-bold hover:underline">Penghapusan Akun</a>
                        dan
                        <a href="{{ route('data-deletion') }}" class="text-[#39D2DD] font-bold hover:underline">Penghapusan Data</a>.
                    </p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-3">7. Kontak</h2>
                    <p>
                        Untuk pertanyaan tentang kebijakan privasi, hubungi VERIDITY melalui email:
                        <a href="mailto:firdarahayu105@gmail.com" class="text-[#39D2DD] font-bold hover:underline">firdarahayu105@gmail.com</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
