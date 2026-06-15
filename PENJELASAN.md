# PENJELASAN PROJECT VERIDITY - BAHAN DEMO FRAMEWORK

Dokumen ini disusun sebagai bahan belajar dan latihan demo mata kuliah Framework. Fokusnya adalah menjelaskan alur sistem, metode yang digunakan, dan kode penting pada folder:

- `veridity-laravel`
- `veridity_mobile`
- `distri`
- `python`

## 1. Ringkasan Sistem

VERIDITY adalah aplikasi forensik digital untuk mendeteksi indikasi:

- foto hasil editan/manipulasi;
- foto yang berpotensi dibuat AI/deepfake;
- dokumen yang dibuat AI, manusia, atau campuran manusia dan AI;
- nota pembayaran palsu/editan dari website distributor.

Sistem terdiri dari empat komponen:

| Komponen | Teknologi | Fungsi |
| --- | --- | --- |
| `veridity-laravel` | Laravel | Website utama, API mobile, database, gateway ke Python |
| `veridity_mobile` | Flutter | Aplikasi mobile user |
| `python` | FastAPI + modul analisis | Engine metode forensik |
| `distri` | Laravel | Website toko/distributor dan validasi nota |

Alur besar:

```text
Website / Mobile / Distri
        |
        v
Laravel API
        |
        v
Python Engine
        |
        v
Database + PDF Report
        |
        v
User melihat hasil
```

## 2. Arsitektur Komunikasi

### 2.1 Website VERIDITY

Alur dari website:

```text
User login
  -> upload foto/dokumen
  -> Laravel validasi file
  -> Laravel simpan file
  -> Laravel panggil Python
  -> Python mengembalikan hasil
  -> Laravel simpan hasil ke database
  -> User melihat detail hasil
  -> User download PDF report
```

### 2.2 Mobile Flutter

Alur dari mobile:

```text
Flutter login
  -> menerima token Sanctum
  -> upload file ke API Laravel
  -> Laravel menjalankan flow analisis yang sama
  -> Flutter menerima JSON hasil
  -> Flutter menampilkan detail dan riwayat
```

Flutter tidak memanggil Python secara langsung. Semua request melewati Laravel agar keamanan, token, dan penyimpanan data tetap terpusat.

### 2.3 Distri

Alur dari `distri`:

```text
Reseller checkout
  -> pilih metode pembayaran
  -> upload nota/bukti pembayaran
  -> distri simpan order
  -> distri kirim nota ke API integrasi VERIDITY
  -> VERIDITY analisis nota sebagai gambar
  -> hasil validasi dikembalikan ke distri
  -> admin melihat status validasi nota
```

## 3. Metode Analisis yang Digunakan

### 3.1 ELA (Error Level Analysis)

ELA dipakai untuk melihat perbedaan tingkat kompresi gambar.

Ide dasarnya:

- Gambar asli biasanya memiliki pola kompresi yang relatif konsisten.
- Jika sebagian area diedit/ditempel, area tersebut bisa memiliki error level berbeda.
- Python membuat peta ELA untuk membantu melihat area yang mencurigakan.

File terkait:

- `python/analysis/ela.py`
- `python/analyze_all.py`
- `veridity-laravel/app/Http/Controllers/Api/ForensicController.php`

Rumus dan cara hitung yang dipakai:

```text
compressed_image = JPEG(original_image, quality)
diff_pixel       = abs(original_pixel - compressed_pixel)
ELA_anomaly      = mean(diff_pixel) + 2 * std(diff_pixel)
A_ela            = max(0, min(100, 100 - (ELA_anomaly * 3)))
```

Penjelasan:

- `diff_pixel` adalah selisih piksel antara gambar asli dan gambar yang dikompresi ulang.
- `mean(diff_pixel)` membaca rata-rata error kompresi seluruh gambar.
- `std(diff_pixel)` membaca penyebaran error. Jika standar deviasi tinggi, berarti ada area tertentu yang error-nya berbeda dari area lain.
- `ELA_anomaly` dibuat dari rata-rata error ditambah dua kali standar deviasi agar area anomali lebih sensitif terdeteksi.
- `A_ela` adalah skor keaslian versi ELA. Semakin tinggi berarti pola kompresi semakin konsisten.

Contoh cara menjelaskan saat demo:

> Sistem menyimpan ulang gambar sebagai JPEG, lalu membandingkan piksel sebelum dan sesudah kompresi. Jika ada area yang pernah ditempel atau diedit, area itu sering memiliki jejak kompresi berbeda sehingga tampak lebih terang di peta ELA. Karena itu ELA tidak langsung memvonis, tetapi menjadi indikator visual dan numerik.

### 3.2 Noise Analysis

Noise analysis memeriksa pola noise/residu piksel.

Ide dasarnya:

- Foto asli memiliki noise yang relatif alami.
- Area tempelan, penghapusan objek, atau export ulang bisa mengubah distribusi noise.
- Hasil noise digunakan sebagai indikator pendukung, bukan satu-satunya keputusan.

File terkait:

- `python/analysis/noise_map.py`
- `python/analyze_all.py`

Rumus dan cara hitung yang dipakai:

```text
blurred_channel      = GaussianBlur(channel, sigma)
noise_channel        = abs(channel - blurred_channel)
block_variance       = var(noise_block_64x64)
mean_variance        = mean(block_variance)
block_variance_std   = std(block_variance)
threshold            = mean_variance * 1.5
```

Jika noise terlalu rendah:

```text
A_noise = max(10, mean_variance * 35)
```

Jika sebaran noise antar blok tidak rata:

```text
deviation_ratio = block_variance_std / (mean_variance * 1.5)
A_noise         = max(20, 100 - (deviation_ratio * 20))
```

Jika noise stabil:

```text
A_noise = 100
```

Penjelasan:

- Gambar dibagi menjadi blok kecil ukuran `64x64`.
- Setiap blok dihitung varians noise-nya.
- Jika variasi antar blok terlalu besar, artinya ada area yang karakter noise-nya berbeda.
- Jika noise terlalu rendah, gambar mungkin terlalu halus karena retouching, smoothing, atau hasil render digital.
- Noise hanya sinyal pendukung. Hasil noise tetap dikorelasikan dengan ELA, metadata, dan deteksi AI.

### 3.3 Metadata Analysis

Metadata analysis membaca informasi file seperti EXIF, software, waktu, dan informasi kamera.

Interpretasi:

- Metadata lengkap dapat mendukung keaslian file.
- Metadata hilang tidak langsung berarti palsu.
- Metadata hilang bisa terjadi karena screenshot, aplikasi chat, export ulang, atau editing.

File terkait:

- `python/analysis/metadata_analysis.py`

Rumus/skor yang dipakai:

```text
A_metadata = 100

jika EXIF/kamera/software/timestamp kosong:
    A_metadata = A_metadata - 30

jika ada jejak software editor:
    A_metadata = A_metadata - 20

A_metadata = max(0, A_metadata)
```

Interpretasi skor:

- `>= 85`: metadata mendukung kamera fisik/asli.
- `60 - 84`: terindikasi editing atau hasil export.
- `< 60`: metadata lemah dan dicurigai rekayasa digital.

Catatan penting untuk demo:

Metadata hilang tidak selalu berarti palsu. Screenshot, WhatsApp, kompresi media sosial, atau export ulang bisa menghapus EXIF. Karena itu metadata dipakai sebagai pendukung, bukan keputusan tunggal.

### 3.4 Deepfake / AI Detection

Deepfake detection dipakai untuk mendeteksi indikasi wajah atau gambar yang dibuat/dimanipulasi AI.

File terkait:

- `python/analysis/deepfake_detector.py`

Rumus dan indikator yang dipakai:

```text
gray_image       = mean(R, G, B)
FFT              = fft2(gray_image)
magnitude_log    = log(abs(fftshift(FFT)) + 1)
radial_variance  = var(radial_profile(magnitude_log))
peak_count       = jumlah puncak spektral tidak wajar
quadrant_symmetry = std(quadrant_means) / mean(quadrant_means)
```

Skor GAN:

```text
gan_score = 0

jika radial_variance > 1.5:
    gan_score += 0.3

jika peak_count > 5:
    gan_score += 0.4

jika quadrant_symmetry < 0.1:
    gan_score += 0.3

A_ai = max(0, min(100, 100 - (gan_score * 100)))
```

Interpretasi:

- `gan_score > 0.7`: indikasi AI sangat tinggi.
- `gan_score > 0.4`: mencurigakan.
- selain itu: rendah/negatif.

Penjelasan sederhana:

Model generatif seperti GAN sering meninggalkan pola frekuensi yang terlalu teratur. Python memakai Fourier Transform untuk melihat pola spektral gambar. Jika pola frekuensinya terlalu simetris atau memiliki puncak berulang, sistem memberi skor kecurigaan AI lebih tinggi.

### 3.5 Document NLP

Analisis dokumen dilakukan pada PDF.

Proses:

1. Python membaca teks dari dokumen.
2. Teks dipotong menjadi kalimat/paragraf.
3. Sistem menghitung metrik bahasa.
4. Sistem mengklasifikasikan bagian teks sebagai human, AI, atau mixed.
5. PDF report dibuat dengan highlight/arsiran.

File terkait:

- `python/main_api.py`
- `python/analyze_document.py`
- `python/analysis/document_detector_core.py`
- `python/analysis/document_pdf_utils.py`
- `python/analysis/document_model_loaders.py`

Rumus dan cara hitung yang dipakai:

```text
sentences = tokenize(raw_text)
result_i  = detector(sentence_i)
label_i   = AI-generated / Human-written / AI-refined
```

Persentase tiap kelas:

```text
P_kelas = (jumlah_kalimat_kelas / total_kalimat) * 100
```

Skor akhir dokumen:

```text
human_p   = persentase Human-written
ai_p      = persentase AI-generated
hybrid_p  = persentase AI-generated & AI-refined
          + persentase Human-written & AI-refined

final_score = human_p
```

Keputusan akhir:

```text
jika final_score >= 80:
    AUTHENTIC (HUMAN WRITTEN)
elif final_score >= 60:
    MIXED TEXT (AI ASSISTED)
else:
    MAYORITAS AI GENERATED
```

Penjelasan:

- Dokumen tidak dinilai dari satu angka mentah saja.
- Teks dipecah menjadi kalimat.
- Setiap kalimat diklasifikasikan oleh model detector.
- Sistem menghitung berapa persen kalimat yang human, AI, atau campuran.
- `final_score` sengaja dibuat mengikuti porsi `Human-written`, karena tujuan akhirnya adalah mengukur kontribusi tulisan manusia.

Tentang label dokumen:

- `AUTHENTIC (HUMAN WRITTEN)` berarti mayoritas kuat gaya tulis manusia.
- `MIXED TEXT (AI ASSISTED)` berarti ada campuran, misalnya manusia menulis lalu AI membantu parafrase, atau AI menulis sebagian paragraf.
- `MAYORITAS AI GENERATED` berarti dominasi kalimat terdeteksi AI, tetapi bukan berarti seluruh dokumen 100 persen AI. Jika masih ada kalimat manusia, bagian itu tetap muncul pada rincian dan arsiran.

Kenapa dokumen PDF lebih stabil daripada DOCX:

- PDF menyimpan posisi teks di halaman, sehingga highlight bisa ditempel pada koordinat teks asli.
- DOCX menyimpan struktur paragraf, style, dan layout yang lebih dinamis. Saat diubah menjadi PDF, format bisa berubah.
- Karena itu untuk hasil arsiran paling konsisten, format PDF lebih disarankan.

Metrik yang bisa dijelaskan di mode detail:

```text
Human-written (%)       = porsi kalimat yang dinilai alami/manusia
AI-generated (%)        = porsi kalimat yang dinilai kuat dibuat AI
AI-refined / Hybrid (%) = porsi kalimat yang berada di area abu-abu/parafrase
```

### 3.6 PDF Report

PDF report berisi:

- ringkasan hasil akhir;
- detail metode foto/dokumen;
- skor/metrik;
- highlight dokumen jika file berupa PDF;
- laporan formal untuk diunduh user.

File terkait:

- `veridity-laravel/app/Services/AuditReportService.php`
- `python/analysis/document_pdf_utils.py`
- `veridity-laravel/resources/views/user/pdf-report.blade.php`

Untuk dokumen PDF, highlight/arsiran dibuat dengan PyMuPDF (`fitz`):

```text
1. Python membuka PDF asli.
2. Sistem mencari posisi kalimat yang ada di classification_map.
3. Jika kalimat AI/mixed ditemukan, sistem menambahkan highlight annotation.
4. Halaman ringkasan dan NLP metrics ditambahkan ke laporan.
5. Metadata PDF disamakan menjadi "Laporan Investigasi Forensik Veridity".
```

Bagian administratif seperti cover, nama dosen, nama mahasiswa, nama kampus, dan lembar pengesahan dikecualikan agar tidak ikut diarsir. Alasannya, bagian administratif sering berupa template dan tidak mewakili gaya penulisan utama dokumen.

### 3.7 Tesseract OCR untuk Validasi Nota

Tesseract adalah engine OCR (Optical Character Recognition). OCR berarti proses mengubah gambar berisi tulisan menjadi teks yang bisa dibaca program.

Di VERIDITY, Tesseract dipakai pada validasi nota pembayaran `distri`.

File terkait:

- `veridity-laravel/app/Services/PaymentProofContentValidator.php`
- `veridity-laravel/config/services.php`

Alur OCR nota:

```text
Nota berupa gambar
  -> Tesseract membaca teks
  -> Laravel menormalisasi teks
  -> Sistem membandingkan dengan data checkout
  -> rekening, nominal, tanggal, nama penerima, dan channel pembayaran dicek
```

Perintah yang dijalankan Laravel:

```text
tesseract path_gambar stdout -l eng+ind
```

Makna perintah:

- `path_gambar`: file nota yang ingin dibaca.
- `stdout`: hasil OCR dikembalikan sebagai teks ke Laravel.
- `-l eng+ind`: Tesseract memakai bahasa Inggris dan Indonesia.

Rumus pengecekan isi nota:

```text
normalized_text = lowercase(text_ocr)
digits_text     = hapus semua karakter selain angka
digits_amount   = angka nominal checkout
digits_account  = angka rekening tujuan toko

rekening_cocok = digits_account ada di digits_text
nominal_cocok  = digits_amount ada di digits_text
tanggal_cocok  = regex tanggal ditemukan di normalized_text
nama_cocok     = token nama penerima ditemukan di normalized_text
channel_cocok  = token metode pembayaran ditemukan di normalized_text
```

Keputusan:

```text
jika rekening/nominal gagal:
    status = failed
elif ada data yang tidak jelas:
    status = review_required
else:
    status = passed
```

Batasan Tesseract:

- OCR bisa gagal jika gambar blur, gelap, miring, terlalu kecil, atau teks tertutup.
- OCR tidak memahami kebenaran transaksi secara bank real-time. Ia hanya membaca teks pada bukti.
- Karena itu admin tetap diberi opsi cek manual ketika hasil OCR tidak tersedia atau server VERIDITY bermasalah.

Contoh kalimat demo:

> Tesseract di sini bukan metode deteksi edit gambar, tetapi alat baca teks. Setelah teks nota terbaca, sistem mencocokkan nomor rekening tujuan, nominal transfer, tanggal, nama penerima, dan channel pembayaran dengan data checkout. Jadi validasi nota punya dua lapis: visual forensic dan content validation.

### 3.8 Rumus Final Foto

Foto dinilai dari gabungan empat indikator:

```text
final_score =
    (A_ela      * 0.30) +
    (A_noise    * 0.30) +
    (A_metadata * 0.20) +
    (A_ai       * 0.20)
```

Keputusan:

```text
jika gan_score > 0.5:
    DEEPFAKE / AI GENERATED
elif final_score < 65 atau metadata manipulatif atau ELA_anomaly > 30:
    MANIPULATED
else:
    AUTHENTIC
```

Kenapa bobotnya begitu:

- ELA dan noise diberi bobot paling besar karena keduanya membaca jejak piksel langsung.
- Metadata diberi bobot sedang karena bisa hilang akibat export biasa.
- AI/deepfake diberi bobot sedang karena menjadi indikator tambahan untuk pola generatif.

Catatan penting:

Hasil akhir tidak boleh dilihat sebagai vonis hukum. Sistem memberi indikasi forensik awal berbasis data piksel, metadata, OCR, dan NLP.

## 4. Penjelasan Kode `veridity-laravel`

### 4.1 Routes Website

File:

```text
veridity-laravel/routes/web.php
```

Fungsi:

- Mendefinisikan route halaman web.
- Route login/register.
- Route dashboard user.
- Route upload analisis.
- Route riwayat.
- Route detail hasil.
- Route download PDF.
- Route admin.

Route penting:

```php
Route::post('/audit/analyze', [ForensicController::class, 'analyze'])->name('audit.analyze');
Route::get('/my-audits', [ForensicController::class, 'history'])->name('user.my-audits');
Route::get('/audit/result/{id}', [ForensicController::class, 'showResult'])->name('user.result');
Route::get('/audit/download-pdf/{id}', [ForensicController::class, 'downloadPDF'])->name('audit.download-pdf');
```

Penjelasan demo:

Route web dipakai oleh user yang membuka website dari browser. Setelah user upload file, route `/audit/analyze` memanggil controller analisis.

### 4.2 Routes API

File:

```text
veridity-laravel/routes/api.php
```

Fungsi:

- Menyediakan endpoint untuk Flutter.
- Menyediakan endpoint integrasi `distri`.
- Menggunakan Sanctum untuk autentikasi mobile.

Endpoint penting:

```php
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/integrations/distri/analyze-proof', [ForensicController::class, 'analyzeDistriProof']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/audits', [ForensicController::class, 'analyze']);
    Route::get('/audits', [ForensicController::class, 'history']);
    Route::get('/audits/{id}', [ForensicController::class, 'show']);
    Route::delete('/audits/{id}', [ForensicController::class, 'destroy']);
    Route::get('/audits/{id}/report', [ForensicController::class, 'downloadPdf']);
});
```

Penjelasan demo:

Flutter tidak mengakses route web. Flutter mengakses route API, mengirim token, lalu menerima response JSON.

### 4.3 AuthController

File:

```text
veridity-laravel/app/Http/Controllers/Api/AuthController.php
```

Fungsi:

- Register user.
- Login user.
- Logout.
- Profile.
- Update profile.
- Lupa password/reset password.
- Dashboard user/admin web.

Konsep penting:

- Untuk web, login memakai session Laravel.
- Untuk mobile, login mengembalikan token Sanctum.

Contoh flow login mobile:

```text
Flutter kirim email/password
  -> Laravel validasi
  -> Laravel cek user
  -> Laravel buat token
  -> Flutter simpan token
```

### 4.4 ForensicController

File:

```text
veridity-laravel/app/Http/Controllers/Api/ForensicController.php
```

Ini controller utama analisis.

Tanggung jawab:

- Validasi file upload.
- Membedakan file foto dan dokumen.
- Menyimpan file.
- Memanggil Python.
- Menyimpan hasil ke tabel `forensic_analyses`.
- Menampilkan riwayat.
- Menghapus audit.
- Download PDF report.
- Menerima integrasi nota dari `distri`.

Alur foto:

```text
Request file JPG/PNG
  -> simpan file
  -> panggil analyze_all.py
  -> decode output JSON Python
  -> simpan ela_score, metadata, noise, final_result
  -> generate/reuse PDF report
```

Alur dokumen:

```text
Request file PDF
  -> simpan file
  -> kirim ke FastAPI Python /analyze-document
  -> simpan NLP result dan classification map
  -> generate PDF report
```

Alur nota `distri`:

```text
distri kirim proof + metadata order
  -> Laravel validasi integration key
  -> Laravel analisis proof sebagai gambar
  -> Laravel cek isi nota: rekening, nominal, tanggal
  -> return status ke distri
```

### 4.5 Model ForensicAnalysis

File:

```text
veridity-laravel/app/Models/ForensicAnalysis.php
```

Fungsi:

- Merepresentasikan tabel `forensic_analyses`.
- Menyimpan hasil analisis.
- Cast JSON/text agar mudah dibaca Laravel.

Data penting:

- `user_id`
- `image_name`
- `s3_path`
- `ela_score`
- `metadata_details`
- `noise_status`
- `final_result`
- `report_pdf_path`
- `report_status`

Penjelasan demo:

Setiap file yang dianalisis menghasilkan satu record `ForensicAnalysis`.

### 4.6 ForensicResource

File:

```text
veridity-laravel/app/Http/Resources/ForensicResource.php
```

Fungsi:

- Mengubah data `ForensicAnalysis` menjadi JSON yang rapi untuk Flutter.
- Menyediakan field seperti label, score, URL file, URL report, dan detail hasil.

Penjelasan demo:

Resource ini menjaga response API tetap konsisten sehingga Flutter tidak perlu membaca struktur database mentah.

### 4.7 AuditReportService

File:

```text
veridity-laravel/app/Services/AuditReportService.php
```

Fungsi:

- Membuat PDF report.
- Mengecek apakah report sudah pernah dibuat.
- Jika sudah ada, report lama dipakai ulang.
- Untuk dokumen, service dapat meminta Python membuat report dengan highlight.

Alasan report disimpan:

- Website dan mobile mengunduh report yang sama.
- Output PDF tidak berbeda antara web dan mobile.
- Sistem tidak perlu generate ulang setiap kali user klik download.

### 4.8 PaymentProofContentValidator

File:

```text
veridity-laravel/app/Services/PaymentProofContentValidator.php
```

Fungsi:

- Memeriksa konten nota pembayaran dari `distri`.
- Mengecek kesesuaian nominal, rekening tujuan, tanggal, dan informasi pembayaran.
- Dipakai sebagai lapisan validasi tambahan selain analisis visual.

Penjelasan demo:

Nota tidak hanya dicek editan gambarnya, tetapi juga dicek isi pembayarannya.

### 4.9 View User

Folder:

```text
veridity-laravel/resources/views/user
```

File penting:

- `dashboard.blade.php`: halaman upload.
- `my-audits.blade.php`: halaman riwayat.
- `result.blade.php`: detail hasil analisis.
- `pdf-report.blade.php`: template PDF report.
- `profile.blade.php`: profil user.

Penjelasan kode view:

- Blade menampilkan data dari controller.
- `result.blade.php` membedakan tampilan foto dan dokumen.
- Foto menampilkan original, ELA map, dan noise map.
- Dokumen menampilkan ringkasan NLP.
- Mode detail analisis berisi penjelasan teknis untuk dosen/peneliti.

## 5. Penjelasan Kode `python`

### 5.1 `main_api.py`

File:

```text
python/main_api.py
```

Fungsi:

- Menjalankan FastAPI.
- Menerima request analisis dokumen.
- Menerima request generate PDF report.
- Mengembalikan JSON ke Laravel.

Contoh alur:

```text
Laravel POST file PDF
  -> FastAPI menerima file
  -> Python ekstrak teks
  -> Python klasifikasi dokumen
  -> Python return hasil JSON
```

### 5.2 `analyze_all.py`

File:

```text
python/analyze_all.py
```

Fungsi:

- Script analisis foto.
- Dipanggil Laravel melalui command line.
- Menggabungkan ELA, noise, metadata, dan deepfake.
- Output akhirnya JSON.

Penjelasan demo:

Laravel menjalankan script ini untuk foto karena analisis gambar membutuhkan library Python.

### 5.3 `analysis/ela.py`

Fungsi:

- Membuat ELA map.
- Menghitung skor ELA.
- Memberi indikasi apakah error compression tampak normal atau mencurigakan.

### 5.4 `analysis/noise_map.py`

Fungsi:

- Membuat noise map.
- Mengukur konsistensi residu/noise gambar.
- Memberi status aman/mencurigakan berdasarkan pola noise.

### 5.5 `analysis/metadata_analysis.py`

Fungsi:

- Membaca metadata file.
- Mengecek EXIF, software, dan informasi file.
- Memberikan indikator apakah metadata mendukung keaslian.

### 5.6 `analysis/deepfake_detector.py`

Fungsi:

- Mendeteksi indikasi wajah/gambar AI.
- Hasilnya menjadi komponen tambahan dalam final result.

### 5.7 `analysis/document_detector_core.py`

Fungsi:

- Inti analisis dokumen.
- Menghitung metrik NLP.
- Menghasilkan probabilitas human, AI, dan hybrid.

### 5.8 `analysis/document_pdf_utils.py`

Fungsi:

- Membuat PDF report dokumen.
- Memberikan highlight/arsiran pada bagian yang terklasifikasi AI/mixed.
- Membuat laporan final yang bisa diunduh user.

## 6. Penjelasan Kode `veridity_mobile`

### 6.1 Struktur Clean Architecture

Folder:

```text
veridity_mobile/lib
```

Struktur utama:

```text
core/
  config/
  network/
  storage/
  theme/
  widgets/

features/
  auth/
  audit/
  profile/
  help/

app/
  app.dart
  routes.dart
```

Penjelasan:

- `core`: kode umum yang dipakai banyak fitur.
- `features`: kode berdasarkan fitur.
- `data`: komunikasi API/repository.
- `domain`: entity/model inti.
- `presentation`: halaman UI.

### 6.2 Konfigurasi API

File:

```text
veridity_mobile/lib/core/config/api_config.dart
```

Fungsi:

- Menentukan base URL Laravel API.
- Bisa diganti saat build APK dengan `--dart-define`.

Contoh build:

```bash
flutter build apk --release --dart-define=VERIDITY_API_BASE_URL=http://PUBLIC_IP/api
```

### 6.3 ApiClient

File:

```text
veridity_mobile/lib/core/network/api_client.dart
```

Fungsi:

- Mengirim request HTTP.
- Menambahkan Authorization token.
- Decode JSON.
- Menangani error API.
- Mengirim multipart upload.

Penjelasan demo:

Semua repository memakai `ApiClient`, sehingga logic HTTP tidak tersebar di semua halaman.

### 6.4 AuthRepository

File:

```text
veridity_mobile/lib/features/auth/data/repositories/auth_repository.dart
```

Fungsi:

- Login.
- Register.
- Logout.
- Menerima token dan user dari Laravel.

### 6.5 AuditRepository

File:

```text
veridity_mobile/lib/features/audit/data/repositories/audit_repository.dart
```

Fungsi:

- Upload file analisis.
- Ambil riwayat.
- Ambil detail.
- Hapus audit.
- Ambil URL report PDF.

### 6.6 Halaman Flutter

File penting:

- `login_page.dart`: halaman login.
- `signup_page.dart`: register.
- `home_page.dart`: halaman utama.
- `upload_file_page.dart`: pilih dan upload file.
- `history_page.dart`: daftar riwayat.
- `audit_detail_page.dart`: detail hasil.
- `profile_page.dart`: profil user.
- `help_page.dart`: bantuan.

Penjelasan demo:

Flutter adalah client mobile. UI dibuat di Flutter, tetapi data tetap diambil dari Laravel API.

## 7. Penjelasan Kode `distri`

### 7.1 Routes

File:

```text
distri/routes/web.php
```

Fungsi:

- Route login/register reseller.
- Route katalog.
- Route detail produk.
- Route keranjang.
- Route checkout.
- Route voucher.
- Route riwayat pesanan.
- Route admin produk.
- Route admin validasi nota.

### 7.2 AuthController

File:

```text
distri/app/Http/Controllers/AuthController.php
```

Fungsi:

- Register reseller.
- Login reseller/admin.
- Logout.

### 7.3 OrderController

File:

```text
distri/app/Http/Controllers/OrderController.php
```

Fungsi:

- Menampilkan katalog/landing.
- Detail produk.
- Checkout.
- Menyimpan order.
- Mengelola voucher.
- Menampilkan riwayat pesanan.
- Menampilkan profil dan alamat.

Alur checkout:

```text
Pilih produk
  -> keranjang / checkout langsung
  -> pilih alamat
  -> pilih voucher
  -> pilih metode pembayaran
  -> upload nota jika diperlukan
  -> simpan order
  -> kirim nota ke VERIDITY
```

### 7.4 CartController

File:

```text
distri/app/Http/Controllers/CartController.php
```

Fungsi:

- Tambah produk ke keranjang.
- Update jumlah item.
- Pilih item yang ingin checkout.
- Hapus item.
- Menghitung total dan voucher pada keranjang.

### 7.5 Admin ProductController

File:

```text
distri/app/Http/Controllers/Admin/ProductController.php
```

Fungsi:

- CRUD produk.
- Sinkron produk dummy.
- Pantau toko.
- Pantau pesanan.
- Validasi nota.
- Terima/tolak manual jika VERIDITY down.

### 7.6 VeridityProofService

File:

```text
distri/app/Services/VeridityProofService.php
```

Fungsi:

- Mengirim nota ke VERIDITY.
- Menggunakan `VERIDITY_BASE_URL`.
- Mengirim `VERIDITY_INTEGRATION_KEY`.
- Menerima hasil validasi.
- Mengubah hasil menjadi status `paid`, `rejected`, atau `checking`.

Penjelasan demo:

Service ini menunjukkan integrasi antar aplikasi menggunakan HTTP API.

### 7.7 Views Distri

Folder:

```text
distri/resources/views
```

File penting:

- `distri/catalog.blade.php`: katalog produk.
- `distri/product-detail.blade.php`: detail produk.
- `distri/cart.blade.php`: keranjang.
- `distri/checkout.blade.php`: checkout.
- `distri/vouchers.blade.php`: voucher.
- `distri/orders.blade.php`: pesanan saya.
- `admin/products/index.blade.php`: kelola produk.
- `admin/products/veridity.blade.php`: validasi nota.
- `admin/products/orders.blade.php`: pantau pesanan.

## 8. Database

### 8.1 PostgreSQL untuk Framework

Pada deployment AWS/framework, database memakai PostgreSQL:

- `veridity_framework`
- `distri_framework`

Laravel migration membuat tabel.

### 8.2 Oracle untuk Basis Data

Untuk mata kuliah Basis Data, Oracle memakai:

- schema `VERIDITY`;
- schema `DISTRI`;
- tablespace `VERIDITY_TS`;
- privilege eksplisit tanpa role `DBA`.

Penjelasan demo:

Satu codebase mendukung dua kebutuhan database melalui `.env`:

```env
DB_CONNECTION=pgsql
```

atau:

```env
DB_CONNECTION=oracle
```

## 9. Deployment AWS

Komponen yang dideploy:

- `veridity-laravel`
- `distri`
- `python`
- PostgreSQL
- S3
- Nginx
- Supervisor

Alur deployment:

```text
EC2 Ubuntu
  -> install PHP, Composer, Node, PostgreSQL, Python
  -> clone project
  -> setup env Laravel
  -> migrate database
  -> setup Python venv
  -> run FastAPI via Supervisor
  -> Nginx expose website
```

S3 digunakan untuk file upload, tetapi perlu diperhatikan bahwa sebagian kode lama masih memakai local storage. Untuk demo cepat, `storage:link` membuat gambar lokal bisa tampil. Untuk produksi penuh, upload harus diarahkan ke disk S3.

## 10. Urutan Demo yang Disarankan

### Demo 1 - Website VERIDITY Foto

1. Login user.
2. Upload foto.
3. Tampilkan loading.
4. Tampilkan detail hasil.
5. Jelaskan ELA, noise, metadata, deepfake.
6. Download PDF report.

### Demo 2 - Website VERIDITY Dokumen

1. Upload PDF.
2. Tunggu analisis.
3. Tampilkan hasil human/AI/mixed.
4. Download PDF.
5. Tunjukkan arsiran/highlight.

### Demo 3 - Flutter

1. Login mobile.
2. Upload file.
3. Tampilkan hasil.
4. Buka history.
5. Download report.

### Demo 4 - Distri

1. Login reseller.
2. Pilih produk.
3. Masukkan ke keranjang.
4. Gunakan voucher.
5. Checkout.
6. Upload nota.
7. Login admin.
8. Buka validasi nota.
9. Jelaskan hasil validasi dari VERIDITY.

## 11. Pertanyaan yang Mungkin Ditanyakan Dosen

### Kenapa Flutter tidak langsung memanggil Python?

Karena Laravel menjadi gateway utama. Token, validasi, database, dan history dikelola Laravel. Jika Flutter langsung ke Python, keamanan dan konsistensi data akan sulit dijaga.

### Kenapa hasil analisis memakai beberapa metode?

Karena satu metode saja tidak cukup. ELA, noise, metadata, dan deepfake saling melengkapi. Final result dibuat dari gabungan indikator.

### Apakah hasil aman/palsu bersifat mutlak?

Tidak. Hasil VERIDITY adalah indikasi forensik awal. Sistem membantu investigasi, bukan menjadi keputusan hukum mutlak.

### Kenapa `distri` dibuat?

`distri` membuktikan integrasi antar aplikasi. Nota pembayaran dari aplikasi lain dapat dikirim ke VERIDITY untuk dianalisis.

### Kenapa report PDF disimpan?

Supaya hasil web dan mobile sama. Jika report selalu dibuat ulang, output bisa berbeda atau lambat.

### Kenapa ada dua database, PostgreSQL dan Oracle?

Karena kebutuhan mata kuliah berbeda. Framework memakai PostgreSQL untuk deployment AWS, sedangkan Basis Data memakai Oracle untuk demonstrasi DBA, tablespace, user, privilege, constraint, dan PDM.

## 12. Kalimat Penutup Demo

VERIDITY dibangun sebagai sistem multi-platform. Laravel berfungsi sebagai pusat backend, website, API, dan penghubung Python. Flutter menjadi aplikasi mobile untuk user. Python menjalankan metode forensik seperti ELA, noise, metadata, deepfake, OCR, NLP, dan PDF report. Distri menjadi aplikasi eksternal yang menguji integrasi API melalui validasi nota pembayaran. Dengan arsitektur ini, sistem dapat melakukan upload file, analisis, penyimpanan hasil, riwayat, validasi nota, dan download report dalam satu ekosistem yang saling terhubung.
