# VERIDITY Project Plan

## Ringkasan Proyek

VERIDITY adalah platform forensik digital untuk mendeteksi indikasi foto atau dokumen yang dibuat AI, diedit, atau merupakan kombinasi tulisan manusia dan AI. Sistem terdiri dari:

- `veridity-laravel`: website utama, API untuk Flutter, autentikasi, riwayat analisis, dashboard user/admin, dan penyimpanan hasil analisis.
- `veridity_mobile`: aplikasi Flutter untuk user melakukan login, upload file, melihat hasil, riwayat, bantuan, dan profil.
- `python`: engine analisis forensik untuk citra dan dokumen, termasuk ELA, noise map, metadata, deepfake detection, document classification, dan PDF report.
- `distri`: website distributor/toko untuk simulasi transaksi, upload nota/bukti pembayaran, dan integrasi call-to-action ke VERIDITY.

Project ini dipakai untuk beberapa mata kuliah dengan kebutuhan output berbeda, sehingga rencana eksekusi harus menjaga dua jalur utama:

1. Mata kuliah Framework
   - Output: website Laravel, aplikasi Flutter, API Laravel, Python engine, integrasi distri.
   - Deployment: `veridity-laravel` di AWS EC2.
   - Database: PostgreSQL.
   - Folder: `veridity-laravel`, `veridity_mobile`, `python`, `distri`.

2. Mata kuliah Basis Data
   - Output: website Laravel berbasis Oracle.
   - Database: Oracle.
   - Folder: `veridity-laravel`, `python`, `distri`.

## Kondisi Saat Ini

### `veridity-laravel`

Status:
- Website utama sudah berjalan secara konsep melalui `routes/web.php`.
- Flow web sudah memiliki route register, login, dashboard user, admin dashboard, upload analisis, riwayat, result, hapus audit, dan download PDF.
- API di `routes/api.php` sudah ada untuk register, login, analyze, history, logout, dan profile, tetapi belum setara dengan kebutuhan flow web dan mobile.
- `ForensicController` sudah menangani dua cabang analisis:
  - citra: menjalankan script Python melalui `PYTHON_PATH` dan `PYTHON_TOOLKIT_SCRIPT`;
  - dokumen: memanggil FastAPI Python di `http://127.0.0.1:8001/analyze-document`.
- Download PDF dokumen memanggil endpoint Python `http://127.0.0.1:8001/generate-pdf-report`.
- Model `ForensicAnalysis` menyimpan metadata, noise status, ELA score, deepfake flag, dan `final_result`.

Risiko:
- Controller API dan web masih tercampur dalam controller yang sama sehingga response JSON dan redirect web perlu distandarkan.
- Endpoint Python masih hardcoded ke `127.0.0.1:8001`.
- Migrasi memakai `json` dan `text`, perlu validasi kompatibilitas untuk PostgreSQL dan Oracle.
- Beberapa query Oracle sudah diakali dengan collection filter karena kendala tipe CLOB/JSON; strategi ini perlu dibakukan.

### `veridity_mobile`

Status:
- Flutter sudah memiliki layar awal seperti splash, login, signup, home, history, help, profile, dan upload foto.
- Auth sudah mencoba konek ke API Laravel.
- IP API masih hardcoded seperti `http://10.253.131.198:8000`.
- Struktur masih screen-based langsung, belum clean architecture.
- State token/user masih dikirim melalui route arguments, belum ada session storage yang rapi.

Risiko:
- Sulit dikembangkan jika tetap memakai struktur layar langsung tanpa domain/data layer.
- Upload analisis belum mengikuti flow website secara lengkap.
- Belum ada kontrak response API yang kuat dari Laravel ke Flutter.

### `python`

Status:
- Tersedia `main_api.py` FastAPI untuk analisis dokumen dan generate report PDF.
- Tersedia modul analisis citra dan dokumen di folder `analysis`.
- `document_pdf_utils.py` sudah membuat halaman kop laporan dan memberi highlight berdasarkan `classification_map`.

Risiko:
- Highlight PDF dokumen masih kurang detail karena pencarian teks per chunk 4 kata bisa gagal pada teks PDF yang terpecah, berbeda encoding, atau berubah whitespace.
- Endpoint hanya fokus dokumen; citra masih dipanggil Laravel lewat shell script.
- Error contract antara Python dan Laravel perlu distandarkan.

### `distri`

Status:
- Website distributor sudah punya auth, katalog, checkout, order history, admin product CRUD, dan daftar order untuk validasi Veridity.
- Nota/bukti transfer disimpan di `public/proofs`.
- Integrasi ke VERIDITY masih berupa komentar di `OrderController::storeOrder`.

Risiko:
- Belum ada token/service credential antar aplikasi.
- Status `veridity_status` masih default `checking` dan belum diupdate berdasarkan hasil analisis.
- Belum ada endpoint callback atau polling hasil analisis dari `veridity-laravel`.

## Prinsip Arsitektur Target

### 1. Laravel sebagai Gateway Aplikasi

`veridity-laravel` menjadi pintu masuk utama untuk web, Flutter, dan integrasi `distri`.

Target:
- Web tetap memakai session auth dan redirect Blade.
- Mobile memakai API token Sanctum.
- `distri` memakai service API key atau token khusus, bukan akun user biasa.
- Python engine tidak dipanggil langsung oleh Flutter atau `distri`.

Alur:

```text
Flutter -> veridity-laravel API -> Python engine -> veridity-laravel DB -> Flutter
Website -> veridity-laravel web route -> Python engine -> veridity-laravel DB -> Website
distri -> veridity-laravel integration API -> Python engine -> result/status -> distri
```

### 2. Dua Profil Database

Karena kebutuhan mata kuliah berbeda, proyek perlu mendukung dua profil environment:

- `framework`: PostgreSQL untuk deployment AWS EC2.
- `basis-data`: Oracle untuk demonstrasi mata kuliah basis data.

Target:
- File `.env.example` mendokumentasikan variabel untuk PostgreSQL, Oracle, Python engine, dan distri integration key.
- Migration dan query tidak bergantung pada fitur yang hanya tersedia di salah satu database tanpa fallback.
- Data JSON besar yang bermasalah di Oracle harus punya strategi aman, misalnya disimpan sebagai text/CLOB dengan cast manual atau accessor.

### 3. Kontrak API Stabil

Laravel API harus punya response yang konsisten agar Flutter dan `distri` tidak membaca struktur yang berubah-ubah.

Format umum:

```json
{
  "status": "success",
  "message": "Analisis selesai",
  "data": {},
  "meta": {}
}
```

Format error:

```json
{
  "status": "error",
  "message": "Validasi gagal",
  "errors": {}
}
```

## Roadmap Eksekusi

### Fase 0 - Stabilkan Kontrak dan Environment

Tujuan:
- Menentukan kontrak antar folder sebelum refactor besar.
- Menghindari perubahan acak yang membuat web, mobile, Python, dan distri saling tidak sinkron.

Task:
- [x] Dokumentasikan endpoint web dan API yang sudah ada di `docs/VERIDITY_PHASE_0_CONTRACT.md`.
- [x] Tentukan base URL dari `.env`, bukan hardcoded:
  - `PYTHON_ENGINE_URL=http://127.0.0.1:8001`
  - `DISTRIBUTOR_API_KEY=...`
  - `APP_API_URL=...`
- [x] Rapikan `.env.example` untuk PostgreSQL dan Oracle tanpa menghapus konfigurasi yang sedang dipakai.
- [x] Buat daftar status hasil analisis yang sama untuk web, mobile, dan distri di `veridity-laravel/config/veridity.php`:
  - `success`: asli/aman;
  - `warning`: mencurigakan/campuran;
  - `danger`: sangat berbahaya/deepfake/AI generated kuat;
  - `error`: gagal dianalisis.
- [x] Tambahkan kontrak awal metode pembayaran `distri` seperti transfer bank, virtual account, e-wallet, QRIS, dan COD.

Deliverable:
- [x] Dokumentasi endpoint dan environment.
- [x] Laravel dapat membaca URL Python dari config.
- [x] Tidak ada hardcoded Python URL baru di controller.
- [x] Kontrak status dan metode pembayaran tersedia sebagai config Laravel.

Verifikasi:
- `php artisan route:list`
- `php artisan test`
- FastAPI health check: `GET /`

### Fase 1 - Laravel API Setara Flow Website

Tujuan:
- Membuat API Laravel bisa menjalankan flow yang sama seperti website sehingga Flutter dapat berjalan lengkap.

Endpoint target:

Auth:
- [x] `POST /api/register`
- [x] `POST /api/login`
- [x] `POST /api/logout`
- [x] `GET /api/profile`

Analysis:
- [x] `POST /api/audits`
- [x] `GET /api/audits`
- [x] `GET /api/audits/{id}`
- [x] `DELETE /api/audits/{id}`
- [x] `GET /api/audits/{id}/report`

Compatibility alias:
- [x] `POST /api/analyze` tetap boleh ada sebagai alias sementara ke `POST /api/audits`.
- [x] `GET /api/history` tetap boleh ada sebagai alias sementara ke `GET /api/audits`.

Task:
- [x] Pisahkan concern response web dan API bila perlu:
  - pertahankan route web yang sudah berjalan;
  - buat method/resource API yang konsisten untuk mobile.
- [x] Gunakan `ForensicResource` untuk response audit.
- [x] Tambahkan validasi file untuk image, PDF, dan DOCX.
- [x] Tambahkan response `visual_results` untuk citra jika ada ELA/noise output.
- [x] Pastikan delete audit memeriksa owner/admin untuk API dan web.
- [x] Tambahkan endpoint report PDF untuk API/mobile.
- [x] Seragamkan response auth mobile dengan `status`, `message`, `data`, `access_token`, `token_type`, serta alias kompatibilitas `user` dan `token`.

Deliverable:
- [x] API siap dikonsumsi Flutter.
- [x] Response JSON stabil.
- [x] Web route lama tetap berfungsi.

Verifikasi:
- [x] Test auth API.
- [x] Test upload image API.
- [x] Test upload PDF/DOCX API dengan Python engine aktif/fake.
- [x] Test history API hanya menampilkan data user terkait.
- [x] Test report PDF mengembalikan `Content-Type: application/pdf`.

### Fase 2 - Flutter Clean Architecture

Tujuan:
- Mengubah `veridity_mobile` dari struktur screen langsung menjadi clean architecture yang mudah dikembangkan.

Struktur target:

```text
lib/
  core/
    config/
    constants/
    errors/
    network/
    storage/
    theme/
    widgets/
  features/
    auth/
      data/
      domain/
      presentation/
    audit/
      data/
      domain/
      presentation/
    profile/
      data/
      domain/
      presentation/
    help/
      presentation/
  app/
    app.dart
    routes.dart
  main.dart
```

Dependency yang disarankan:
- `http` tetap bisa dipakai untuk scope aman.
- [ ] Tambahkan `flutter_secure_storage` atau `shared_preferences` untuk token.
- [x] Tambahkan `file_picker` atau `image_picker` untuk upload file.
- [x] Tambahkan state management sederhana melalui `SessionStore` in-memory lebih dulu, sebelum persistence formal.

Task:
- [x] Pindahkan base URL ke config via `VERIDITY_API_BASE_URL`.
- [x] Buat `ApiClient` untuk header token, JSON decode, multipart upload, dan error handling.
- [x] Buat `AuthRepository` untuk login/register/logout.
- [x] Buat `AuditRepository` untuk upload, history, detail, delete, dan report URL.
- [x] Buat entity/model:
  - `UserEntity`
  - `AuthSession`
  - `AuditEntity`
- [x] Refactor layar:
  - Login dan signup memakai repository, bukan `http.post` langsung.
  - Home menampilkan CTA upload image/document.
  - Upload mendukung JPG, PNG, PDF, DOCX.
  - History membaca `/api/audits`.
  - Profile membaca token/session yang tersimpan.
- [x] Tambahkan detail result mobile untuk status, score, metadata summary, jumlah klasifikasi, delete, dan URL report PDF.
- [x] Rapikan UI agar konsisten dengan website:
  - warna utama biru/indigo Veridity;
  - bottom navigation konsisten;
  - loading state, empty state, dan error state jelas;
  - tidak bergantung pada image URL eksternal yang mudah expired.

Deliverable:
- [x] Flutter memakai fondasi clean architecture.
- [x] Mobile dapat login, register, upload file, melihat history, dan logout.
- [x] Mobile dapat melihat detail hasil dan URL report PDF.
- [x] Tidak ada IP hardcoded di screen.

Verifikasi:
- `flutter analyze`
- `flutter test`
- Manual test Android emulator/device dengan API Laravel lokal.

### Fase 3 - Python Engine dan PDF Highlight Dokumen

Tujuan:
- Memperbaiki detail arsiran/highlight PDF dokumen agar lebih akurat dan terlihat profesional.

Masalah utama:
- `generate_annotated_pdf` mencari potongan 4 kata dengan `page.search_for(chunk)`.
- PDF sering memecah teks per span, line, hyphenation, atau encoding sehingga banyak kalimat tidak ter-highlight.

Strategi perbaikan:
- Ekstrak teks per halaman dengan koordinat menggunakan PyMuPDF `page.get_text("dict")`.
- Normalisasi teks:
  - lowercase untuk matching;
  - hilangkan spasi ganda;
  - tangani newline;
  - tangani tanda baca dasar.
- Buat matcher berbasis token window:
  - pecah sentence dari `classification_map` menjadi token;
  - cari token sequence di token halaman;
  - gabungkan bounding box token yang cocok;
  - highlight per line agar tidak terlalu tebal.
- Simpan statistik highlight:
  - total sentence diklasifikasi;
  - total sentence berhasil ditemukan;
  - total highlight annotation;
  - daftar sentence yang gagal ditemukan untuk debugging.

Task:
- [x] Tambah unit test untuk `document_pdf_utils`.
- [x] Buat PDF sample kecil di test fixture.
- [x] Test bahwa label non-human menghasilkan annotation.
- [x] Test bahwa `Human-written` tidak di-highlight.
- [x] Perbaiki warna dan legenda agar sama dengan label Python.
- [x] Pastikan hasil PDF tetap punya halaman kop di awal.
- [x] Perbaiki route unduh PDF mobile agar bisa dibuka dari browser eksternal memakai token query.
- [x] Tambahkan fallback PDF ringkasan untuk hasil analisis foto dan DOCX lewat Laravel DomPDF.

Deliverable:
- [x] Download PDF dokumen memiliki arsiran lebih detail.
- [x] Laravel tetap memanggil endpoint yang sama.
- [x] Tidak merusak hasil analisis dokumen.
- [x] Tombol download PDF di Flutter dapat memakai endpoint mobile tanpa header Authorization.

Verifikasi:
- [x] `php artisan test`
- [x] Compile check modul Python PDF.
- [x] Test langsung `document_pdf_utils` karena `pytest` belum terpasang di Python global lokal.
- [ ] Manual call `POST /generate-pdf-report`
- [ ] Buka PDF hasil dan cek highlight per kalimat/line.

### Fase 4 - Integrasi `distri` ke VERIDITY dan Metode Pembayaran

Tujuan:
- Saat user checkout di `distri`, user dapat memilih metode pembayaran terlebih dahulu seperti pola aplikasi Alfagift, lalu upload nota/bukti pembayaran jika metode tersebut membutuhkan bukti manual. Bukti pembayaran yang diunggah dikirim ke VERIDITY untuk dianalisis asli/palsu.

Arsitektur target:
- `distri` tetap menyimpan order dan proof lokal.
- `distri` menyediakan pilihan metode pembayaran pada checkout:
  - transfer bank: BCA, BNI, BRI, Mandiri, dan bank lain yang dibutuhkan saat demo;
  - virtual account: VA bank terpilih dengan nomor pembayaran simulasi;
  - e-wallet: DANA, OVO, GoPay, ShopeePay, dan channel lain yang mudah ditambahkan lewat config;
  - QRIS: tampilkan QR pembayaran simulasi dan instruksi upload bukti;
  - cash on delivery atau bayar di tempat jika diperlukan untuk simulasi.
- Setiap metode pembayaran punya instruksi, nomor/tujuan pembayaran, dan aturan bukti pembayaran yang jelas.
- `distri` mengirim proof ke `veridity-laravel` melalui integration API.
- `veridity-laravel` menjalankan analisis citra melalui Python.
- `veridity-laravel` mengembalikan status ringkas ke `distri`.
- `distri` menyimpan:
  - `payment_method`
  - `payment_channel`
  - `payment_status`
  - `veridity_status`
  - `veridity_audit_id`
  - `veridity_score`
  - `veridity_message`

Endpoint target di `veridity-laravel`:

- `POST /api/integrations/distri/analyze-proof`

Request:
- Header: `X-Veridity-Integration-Key`
- Multipart:
  - `proof`
  - `order_id`
  - `payment_method`
  - `payment_channel`
  - `source=distri`

Response:

```json
{
  "status": "success",
  "message": "Nota berhasil dianalisis",
  "data": {
    "audit_id": 12,
    "summary_label": "MENCURIGAKAN (TERINDIKASI REKAYASA)",
    "summary_color": "warning",
    "final_score": 63.5
  }
}
```

Task:
- Tambahkan kolom di `distri.orders`:
  - `payment_method`
  - `payment_channel`
  - `payment_status`
  - `payment_instruction`
  - `veridity_audit_id`
  - `veridity_score`
  - `veridity_message`
  - `veridity_checked_at`
- Tambahkan daftar metode pembayaran di config agar admin/developer mudah menambah channel tanpa mengubah banyak view.
- Ubah halaman checkout agar user memilih metode pembayaran sebelum upload nota.
- Untuk metode yang tidak membutuhkan upload bukti, simpan order tanpa proof dan tandai `veridity_status` sebagai `not_required`.
- Ubah `OrderController::storeOrder` untuk `Http::attach()` ke VERIDITY.
- Jika VERIDITY gagal, order tetap tersimpan dengan status `checking` atau `error`.
- Tampilkan hasil validasi di:
  - riwayat pesanan reseller;
  - halaman admin `veridity-validation`.
- Tambahkan retry action untuk admin jika analisis gagal.

Deliverable:
- Checkout `distri` memiliki pilihan metode pembayaran seperti transfer bank, virtual account, e-wallet, QRIS, dan opsi simulasi lain yang relevan.
- Upload nota di `distri` memicu analisis Veridity jika metode pembayaran membutuhkan bukti manual.
- Admin distributor dapat melihat mana nota aman/mencurigakan/berbahaya.

Verifikasi:
- Buat order dengan metode transfer bank dan proof asli.
- Buat order dengan metode e-wallet/QRIS dan proof editan.
- Buat order dengan metode yang tidak membutuhkan proof jika diaktifkan.
- Pastikan status di database `orders` berubah.
- Pastikan admin page menampilkan status.

### Fase 5 - Database PostgreSQL dan Oracle

Tujuan:
- Menjaga satu codebase tetap bisa dipakai untuk kebutuhan Framework dan Basis Data.

Task PostgreSQL:
- Pastikan `.env.framework` atau dokumentasi `.env` memakai:
  - `DB_CONNECTION=pgsql`
  - `DB_HOST`
  - `DB_PORT=5432`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- Jalankan migration dan seed.
- Pastikan `json` cast Laravel berjalan normal.

Task Oracle:
- Pastikan `.env.basis-data` atau dokumentasi `.env` memakai:
  - `DB_CONNECTION=oracle`
  - `DB_HOST`
  - `DB_PORT=1521`
  - `DB_SERVICE_NAME` atau `DB_SID`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- Review migration untuk field JSON/text besar:
  - `metadata_details`
  - `final_result`
  - `noise_status`
- Hindari query JSON langsung di Oracle jika field menjadi CLOB.
- Untuk statistik admin, gunakan collection filtering atau kolom ringkas tambahan:
  - `summary_label`
  - `summary_color`
  - `final_score`

Rekomendasi:
- Tambahkan kolom ringkas di `forensic_analyses` agar dashboard tidak perlu membaca JSON besar:
  - `file_type`
  - `summary_label`
  - `summary_color`
  - `final_score`
  - `analysis_source`

Deliverable:
- Migration berjalan di PostgreSQL.
- Migration berjalan di Oracle.
- Dashboard admin tidak bergantung pada query JSON yang rawan error Oracle.

Verifikasi:
- `php artisan migrate:fresh --seed` untuk profil PostgreSQL.
- `php artisan migrate:fresh --seed` untuk profil Oracle.
- Test dashboard admin dan history user di dua database.

### Fase 6 - Deployment AWS EC2 untuk Mata Kuliah Framework

Tujuan:
- Menyiapkan deployment Laravel + Python engine + PostgreSQL di AWS EC2.

Komponen:
- Nginx atau Apache untuk Laravel.
- PHP-FPM sesuai versi Laravel.
- Composer dependencies.
- Node/Vite build assets.
- PostgreSQL.
- Python virtual environment untuk FastAPI.
- Process manager:
  - Supervisor untuk Laravel queue jika dipakai.
  - systemd atau Supervisor untuk FastAPI.

Task:
- Buat deployment checklist.
- Konfigurasi `.env` production.
- Jalankan:
  - `composer install --no-dev --optimize-autoloader`
  - `php artisan key:generate`
  - `php artisan migrate --force`
  - `php artisan storage:link`
  - `npm ci`
  - `npm run build`
- Jalankan Python:
  - buat venv;
  - install `requirements.txt`;
  - start FastAPI di localhost port 8001;
  - Laravel memanggil `PYTHON_ENGINE_URL=http://127.0.0.1:8001`.
- Atur upload size dan timeout:
  - Nginx `client_max_body_size`;
  - PHP `upload_max_filesize`, `post_max_size`, `max_execution_time`;
  - FastAPI service timeout.

Deliverable:
- Website VERIDITY dapat diakses dari domain/IP EC2.
- Upload analisis citra dan dokumen berjalan di server.
- Flutter bisa diarahkan ke base URL EC2.

Verifikasi:
- Login/register website.
- Upload image.
- Upload PDF/DOCX.
- Download report PDF.
- Cek log Laravel dan Python.

## Urutan Prioritas yang Direkomendasikan

1. Stabilkan Laravel API dan environment.
2. Perbaiki Python PDF highlight dokumen.
3. Refactor Flutter clean architecture dan sambungkan ke API baru.
4. Integrasikan `distri` ke API VERIDITY.
5. Rapikan kompatibilitas database PostgreSQL dan Oracle.
6. Siapkan deployment AWS EC2.
7. Lakukan end-to-end demo untuk masing-masing mata kuliah.

Alasan:
- Flutter dan `distri` bergantung pada API yang stabil.
- PDF highlight dokumen adalah perbaikan domain spesifik yang bisa dilakukan paralel setelah kontrak Python jelas.
- Database dan deployment lebih aman dilakukan setelah flow fitur tidak berubah besar.

## Checklist Output Per Mata Kuliah

### Framework

- [ ] Website Laravel user dapat register, login, upload foto/dokumen, melihat hasil, melihat riwayat, hapus riwayat, dan download PDF.
- [ ] Website Laravel admin dapat melihat dashboard, audit logs, detail audit, dan blacklist/fraud cases.
- [ ] Flutter dapat register, login, upload foto/dokumen, melihat hasil, history, profile, help, dan logout.
- [ ] Flutter memakai clean architecture.
- [ ] Laravel API memakai Sanctum token untuk mobile.
- [ ] Python engine berjalan untuk citra dan dokumen.
- [ ] `distri` dapat mengirim nota ke VERIDITY dan menampilkan status validasi.
- [ ] Deployment AWS EC2 menggunakan PostgreSQL.

### Basis Data

- [ ] Website Laravel berjalan dengan Oracle.
- [ ] Tabel user dan forensic analysis dapat menyimpan hasil analisis.
- [ ] Dashboard user/admin dapat membaca data Oracle tanpa error CLOB/JSON.
- [ ] Python engine tetap berjalan sebagai service pendukung.
- [ ] `distri` berjalan dengan Oracle untuk produk, order, dan status Veridity.
- [ ] Demo query/relasi database jelas:
  - users;
  - forensic_analyses;
  - products;
  - orders.

## Acceptance Criteria End-to-End

### Skenario 1 - Analisis Foto dari Website

1. User login di website VERIDITY.
2. User upload JPG/PNG.
3. Laravel menyimpan file.
4. Laravel memanggil Python image analysis.
5. Hasil ELA, noise, metadata, deepfake, dan final result tersimpan.
6. User melihat result page.
7. User melihat riwayat.

### Skenario 2 - Analisis Dokumen dari Website

1. User login di website VERIDITY.
2. User upload PDF/DOCX.
3. Laravel memanggil FastAPI `/analyze-document`.
4. Hasil klasifikasi AI/manusia/campuran tersimpan.
5. User membuka result page.
6. User download PDF report.
7. PDF report memiliki arsiran detail sesuai classification map.

### Skenario 3 - Analisis dari Flutter

1. User login dari Flutter.
2. Token tersimpan aman.
3. User memilih file foto/dokumen.
4. Flutter upload ke Laravel API.
5. Flutter menampilkan status loading.
6. Flutter menampilkan hasil.
7. Flutter history sinkron dengan website.

### Skenario 4 - Nota dari `distri`

1. Reseller login di `distri`.
2. Reseller checkout produk.
3. Reseller memilih metode pembayaran, misalnya transfer bank, virtual account, e-wallet, QRIS, atau opsi simulasi lain.
4. Jika metode pembayaran membutuhkan bukti manual, reseller upload proof pembayaran.
5. `distri` menyimpan order beserta metode pembayaran.
6. `distri` mengirim proof ke VERIDITY integration API.
7. VERIDITY menganalisis proof sebagai citra.
8. `distri` menyimpan status validasi.
9. Admin distributor melihat status nota di halaman validasi.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| API Laravel berubah saat Flutter dikerjakan | Flutter sering rusak | Kunci kontrak API dulu di Fase 1 |
| Oracle bermasalah dengan JSON/CLOB | Dashboard gagal | Tambah kolom ringkas dan hindari query JSON raw |
| Python engine lambat | Request timeout | Naikkan timeout, optimasi model, pertimbangkan queue untuk produksi |
| PDF text matching tidak akurat | Arsiran report kurang detail | Gunakan token-coordinate matching berbasis PyMuPDF |
| IP API Flutter hardcoded | Tidak fleksibel saat pindah jaringan/deploy | Gunakan config per environment |
| `distri` gagal menghubungi VERIDITY | Order gagal total | Order tetap tersimpan, status `error/checking`, sediakan retry |
| Deployment EC2 gagal karena dependency native Python | Demo terganggu | Siapkan deployment checklist dan test di environment bersih |

## Catatan Eksekusi Berikutnya

Eksekusi sebaiknya dilakukan per fase, bukan semua sekaligus. Rekomendasi pertama adalah Fase 1 karena akan menjadi fondasi untuk Flutter dan `distri`.

Urutan eksekusi teknis berikutnya:

1. Buat branch kerja khusus, misalnya `codex/veridity-api-foundation`.
2. Implementasi Fase 1 dengan test API Laravel.
3. Jalankan verifikasi Laravel + Python.
4. Lanjut Fase 3 untuk PDF highlight dokumen.
5. Lanjut Fase 2 untuk Flutter clean architecture.
6. Lanjut Fase 4 untuk `distri`.

File yang perlu diprioritaskan saat eksekusi Fase 1:

- `veridity-laravel/routes/api.php`
- `veridity-laravel/app/Http/Controllers/Api/AuthController.php`
- `veridity-laravel/app/Http/Controllers/Api/ForensicController.php`
- `veridity-laravel/app/Http/Resources/ForensicResource.php`
- `veridity-laravel/app/Models/ForensicAnalysis.php`
- `veridity-laravel/config/services.php`
- `veridity-laravel/.env.example`
- `python/main_api.py`
- `python/analysis/document_pdf_utils.py`
