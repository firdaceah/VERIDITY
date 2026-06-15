# Panduan Deploy VERIDITY ke Render + Supabase

Panduan ini digunakan ketika server tidak memakai AWS atau Oracle Cloud. Arsitektur yang dipakai:

```text
Flutter Play Store
    |
    v
Laravel API di Render
    |
    +-- PostgreSQL di Supabase
    |
    +-- Python forensic engine di Render
```

Catatan penting: free tier cocok untuk demo dan tahap awal. Analisis foto/dokumen bisa lebih lambat karena resource gratis kecil.

## 1. Siapkan Repository

Pastikan project sudah ada di GitHub dengan struktur:

```text
VERIDITY/
  veridity-laravel/
  python/
  veridity_mobile/
  distri/
```

Untuk Play Store, yang dibutuhkan server hanya:

- `veridity-laravel`
- `python`
- PostgreSQL Supabase

Folder `veridity_mobile` nanti hanya dipakai untuk build `.aab`.

## 2. Buat Database PostgreSQL di Supabase

1. Buka Supabase.
2. Buat project baru.
3. Simpan password database dengan aman.
4. Masuk ke `Project Settings > Database`.
5. Ambil data koneksi:
   - Host
   - Port
   - Database name
   - User
   - Password

Biasanya formatnya seperti:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxx
DB_PASSWORD=password_supabase
```

Jika port `6543` bermasalah saat migration, coba gunakan direct connection port `5432` dari halaman Supabase.

## 3. Deploy Python Engine ke Render

Python engine harus dibuat dulu karena URL-nya akan dipakai oleh Laravel.

1. Buka Render.
2. Klik `New +`.
3. Pilih `Web Service`.
4. Connect repository GitHub VERIDITY.
5. Isi:
   - Name: `veridity-python`
   - Root Directory: `python`
   - Runtime: `Docker`
   - Dockerfile Path: `Dockerfile.render`
   - Instance Type: `Free`

Render akan membaca file:

```text
python/Dockerfile.render
```

Setelah deploy selesai, buka URL Python:

```text
https://veridity-python.onrender.com
```

Jika benar, hasilnya:

```json
{
  "status": "ok",
  "message": "Veridity Document API is running"
}
```

Simpan URL ini untuk `.env` Laravel:

```env
PYTHON_ENGINE_URL=https://veridity-python.onrender.com
```

## 4. Deploy Laravel API ke Render

1. Buka Render.
2. Klik `New +`.
3. Pilih `Web Service`.
4. Connect repository GitHub VERIDITY.
5. Isi:
   - Name: `veridity-laravel`
   - Root Directory: `veridity-laravel`
   - Runtime: `Docker`
   - Dockerfile Path: `Dockerfile.render`
   - Instance Type: `Free`

Render akan membaca file:

```text
veridity-laravel/Dockerfile.render
```

## 5. Isi Environment Laravel di Render

Masuk ke service `veridity-laravel > Environment`, lalu isi:

```env
APP_NAME=VERIDITY
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://veridity-laravel.onrender.com

DB_CONNECTION=pgsql
DB_HOST=HOST_SUPABASE
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=USER_SUPABASE
DB_PASSWORD=PASSWORD_SUPABASE

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public

PYTHON_ENGINE_URL=https://veridity-python.onrender.com
PYTHON_PATH=python
PYTHON_TOOLKIT_SCRIPT=
TESSERACT_PATH=tesseract
```

`APP_KEY` harus dibuat dulu. Cara paling mudah:

1. Jalankan lokal di folder `veridity-laravel`:

```bash
php artisan key:generate --show
```

2. Copy hasilnya ke Render:

```env
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxx
```

## 6. Jalankan Migration dan Seeder

Setelah Laravel berhasil deploy, buka `veridity-laravel > Shell` di Render.

Jalankan:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
```

Jika `storage:link` gagal karena environment container, aplikasi masih bisa berjalan, tetapi file public harus dipastikan tersimpan dan diakses dari disk `public`.

## 7. Cek Upload File Besar

File Render Laravel memakai Apache. Di `render-apache.conf` sudah ditambahkan:

```apache
LimitRequestBody 52428800
```

Artinya limit upload sekitar 50 MB.

Jika upload masih gagal:

1. Cek log Laravel di Render.
2. Cek apakah validasi Laravel membatasi ukuran file.
3. Cek apakah Python service timeout karena free tier lambat.

## 8. Tes API Laravel

Buka:

```text
https://veridity-laravel.onrender.com
```

Lalu tes fitur:

- Register
- Login
- Upload foto kecil
- Upload foto besar
- Upload dokumen PDF
- Riwayat
- Detail analisa
- Unduh PDF

Jika analisa dokumen gagal, cek URL:

```text
https://veridity-python.onrender.com
```

Jika Python service tidur, request pertama bisa lambat. Tunggu beberapa detik lalu ulangi.

## 9. Update Flutter API Base URL

Jika API Laravel sudah online, build Flutter dengan:

```bash
flutter build appbundle --release --dart-define=VERIDITY_API_BASE_URL=https://veridity-laravel.onrender.com/api
```

Untuk tes di HP:

```bash
flutter run --release --dart-define=VERIDITY_API_BASE_URL=https://veridity-laravel.onrender.com/api
```

## 10. Catatan Risiko Free Tier

Free tier bisa memiliki batasan:

- Service bisa sleep saat tidak dipakai.
- Request pertama setelah sleep bisa lambat.
- Python dengan `torch`, `transformers`, dan NLP bisa berat.
- Render PostgreSQL free memiliki batas waktu, jadi database lebih aman di Supabase.
- File yang disimpan di local container bisa hilang saat redeploy.

Untuk Play Store yang lebih stabil, nanti idealnya file upload dipindah ke object storage seperti Supabase Storage atau Cloudinary.

## 11. Checklist Sebelum Upload Play Store

Pastikan semua ini aman:

- `https://veridity-laravel.onrender.com` bisa dibuka.
- `https://veridity-python.onrender.com` bisa dibuka.
- Login dari Flutter berhasil.
- Upload foto berhasil.
- Upload dokumen PDF berhasil.
- Riwayat tampil.
- Detail analisa tampil.
- Download PDF berhasil.
- Tidak ada URL lokal seperti `127.0.0.1`, `localhost`, atau IP laptop di aplikasi Flutter.

## 12. Sumber Resmi

- Render Pricing: https://render.com/pricing
- Supabase Pricing: https://supabase.com/pricing
- Render Web Services: https://docs.render.com/web-services
