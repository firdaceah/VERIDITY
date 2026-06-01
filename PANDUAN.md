# PANDUAN FASE 5 - PostgreSQL dan Oracle

Panduan ini dipakai agar satu codebase VERIDITY tetap bisa berjalan untuk dua kebutuhan:

- Mata kuliah Framework: `veridity-laravel`, `veridity_mobile`, `python`, dan `distri` memakai PostgreSQL.
- Mata kuliah Basis Data: `veridity-laravel`, `python`, dan `distri` memakai Oracle.

## 1. Prinsip Switching Database

Kedua aplikasi Laravel sudah memiliki koneksi `pgsql` dan `oracle` di `config/database.php`. Jadi perubahan utama cukup dilakukan melalui `.env`.

Setelah mengubah `.env`, selalu jalankan:

```bash
php artisan config:clear
php artisan cache:clear
```

Jika pernah menjalankan cache config production, jalankan juga:

```bash
php artisan optimize:clear
```

## 2. Konfigurasi PostgreSQL - veridity-laravel

Gunakan konfigurasi ini pada `veridity-laravel/.env` untuk kebutuhan Framework/AWS.

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=veridity_framework
DB_USERNAME=postgres
DB_PASSWORD=password_postgres

PYTHON_PATH="C:/Users/user/AppData/Local/Programs/Python/Python311/python.exe"
PYTHON_TOOLKIT_SCRIPT="C:/Users/user/PENS/Semester-4/VERIDITY/python/analyze_all.py"
PYTHON_ENGINE_URL=http://127.0.0.1:8001
TESSERACT_PATH="C:/Program Files/Tesseract-OCR/tesseract.exe"

VERIDITY_INTEGRATION_KEY=veridity-distri-demo-key
VERIDITY_INTEGRATION_USER_ID=1
```

Catatan penting:

- Jangan aktifkan `DB_CONNECTION=oracle` bersamaan dengan `DB_CONNECTION=pgsql`.
- Path Windows yang memiliki spasi harus memakai tanda kutip dua seperti contoh `TESSERACT_PATH`.
- Jika Android fisik dipakai untuk Flutter, `127.0.0.1` tidak bisa diakses dari HP. Gunakan IP laptop dalam jaringan yang sama.

## 3. Konfigurasi PostgreSQL - distri

Gunakan konfigurasi ini pada `distri/.env`.

```env
APP_URL=http://127.0.0.1:8002

VERIDITY_BASE_URL=http://127.0.0.1:8000
VERIDITY_INTEGRATION_KEY=veridity-distri-demo-key

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=distri_framework
DB_USERNAME=postgres
DB_PASSWORD=password_postgres
```

Nilai `VERIDITY_INTEGRATION_KEY` di `distri` harus sama persis dengan `VERIDITY_INTEGRATION_KEY` di `veridity-laravel`. Nilai ini adalah shared secret lokal untuk membuktikan bahwa request validasi nota benar-benar berasal dari aplikasi `distri`.

## 4. Membuat Database PostgreSQL

Pilih salah satu cara.

Melalui terminal:

```bash
createdb veridity_framework
createdb distri_framework
```

Atau melalui SQL:

```sql
CREATE DATABASE veridity_framework;
CREATE DATABASE distri_framework;
```

Setelah database dibuat, jalankan migrasi.

```bash
cd veridity-laravel
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test

cd ../distri
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test
```

## 5. Menjalankan Python Engine

Python dipakai oleh Laravel untuk analisis foto, dokumen, OCR, dan generate PDF report.

```bash
cd python
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main_api:app --host 127.0.0.1 --port 8001
```

Jika modul OCR membutuhkan Tesseract, pastikan path di `.env` benar:

```env
TESSERACT_PATH="C:/Program Files/Tesseract-OCR/tesseract.exe"
```

## 6. Menjalankan Website Lokal

Jalankan `veridity-laravel` di port 8000.

```bash
cd veridity-laravel
php artisan serve --host=127.0.0.1 --port=8000
```

Jalankan `distri` di port 8002.

```bash
cd distri
php artisan serve --host=127.0.0.1 --port=8002
```

Jika `veridity-laravel` memakai `http://127.0.0.1:8000`, maka `distri` sebaiknya memakai:

```env
APP_URL=http://127.0.0.1:8002
VERIDITY_BASE_URL=http://127.0.0.1:8000
```

## 7. Menjalankan Flutter

Gunakan base URL API Laravel. Untuk emulator Android biasanya bisa memakai IP khusus emulator. Untuk HP fisik, pakai IP laptop.

```bash
cd veridity_mobile
flutter clean
flutter pub get
flutter analyze
flutter run --dart-define=VERIDITY_API_BASE_URL=http://IP_LAPTOP:8000/api
```

Contoh:

```bash
flutter run --dart-define=VERIDITY_API_BASE_URL=http://192.168.1.20:8000/api
```

## 8. Konfigurasi Oracle - Basis Data

Untuk kebutuhan mata kuliah Basis Data, gunakan Oracle pada `.env`.

Contoh `veridity-laravel/.env` atau `distri/.env`:

```env
DB_CONNECTION=oracle
DB_HOST=127.0.0.1
DB_PORT=1521
DB_SERVICE_NAME=XE
DB_USERNAME=VERIDITY
DB_PASSWORD=password_oracle
```

Jika Oracle memakai SID, gunakan:

```env
DB_SID=XE
```

Pastikan listener Oracle aktif dan service name dapat diakses.

```bash
lsnrctl status
```

## 9. Catatan Kompatibilitas PostgreSQL dan Oracle

Bagian yang perlu diperhatikan:

- Field besar seperti `metadata_details`, `noise_status`, `final_result`, `veridity_validation_details`, dan `payment_instruction` dapat menjadi `CLOB` di Oracle.
- Jangan membuat query JSON langsung pada field besar Oracle. Ambil data terlebih dahulu, lalu decode di PHP.
- Untuk PostgreSQL, cast JSON Laravel berjalan lebih natural.
- Statistik dashboard sebaiknya memakai kolom ringkas seperti status, score, label, dan warna, bukan membaca isi JSON/CLOB besar.
- Migration Laravel saat ini sudah memakai tipe umum seperti `string`, `text`, `decimal`, `boolean`, dan `foreignId`, sehingga relatif aman untuk PostgreSQL dan Oracle melalui driver Laravel/OCI8.

## 10. Troubleshooting

Jika muncul `could not find driver`:

- PostgreSQL: aktifkan extension `pdo_pgsql` di `php.ini`.
- Oracle: pastikan `oci8` dan `pdo_oci` sesuai versi PHP/Instant Client.

Jika Laravel masih membaca database lama:

```bash
php artisan config:clear
php artisan optimize:clear
```

Jika Flutter tidak bisa login dari HP:

- Jangan pakai `127.0.0.1` di HP.
- Pakai IP laptop, misalnya `http://192.168.1.20:8000/api`.
- Jalankan Laravel dengan host yang bisa diakses jaringan:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Jika upload file besar gagal:

- Naikkan `upload_max_filesize` dan `post_max_size` di PHP.
- Pastikan validasi Laravel tidak lebih kecil dari ukuran file.
- Pastikan koneksi HP dan laptop stabil.

## 11. Checklist Verifikasi Fase 5

PostgreSQL:

- [ ] `veridity-laravel` berhasil `php artisan migrate:fresh --seed`.
- [ ] `distri` berhasil `php artisan migrate:fresh --seed`.
- [ ] Login/register website berjalan.
- [ ] Upload foto dan dokumen berjalan.
- [ ] Download PDF report berjalan.
- [ ] Checkout `distri` berjalan.
- [ ] Validasi nota dari `distri` masuk ke VERIDITY.
- [ ] Flutter login, upload, history, detail, dan download PDF berjalan.

Oracle:

- [ ] `veridity-laravel` berhasil migrasi ke Oracle.
- [ ] `distri` berhasil migrasi ke Oracle.
- [ ] Dashboard user/admin tidak error pada field CLOB.
- [ ] CRUD produk berjalan.
- [ ] Order dan validasi nota berjalan.
- [ ] Analisis dokumen dan foto tetap memakai Python engine yang sama.

## 12. Cara Cek Implementasi Oracle di SQL Developer

Bagian ini dipakai untuk membuktikan poin laporan bagian implementasi database dan DBA.

### 12.1 Cek user aktif

Jalankan saat login sebagai user aplikasi, misalnya `VERIDITY`.

```sql
SHOW USER;
```

Atau:

```sql
SELECT USER FROM DUAL;
```

### 12.2 Cek tablespace

Jika login sebagai user DBA/SYS:

```sql
SELECT TABLESPACE_NAME, STATUS, CONTENTS
FROM DBA_TABLESPACES
WHERE TABLESPACE_NAME = 'VERIDITY_TS';
```

Jika login sebagai user biasa dan tidak punya akses `DBA_TABLESPACES`, gunakan:

```sql
SELECT TABLESPACE_NAME
FROM USER_TABLES
GROUP BY TABLESPACE_NAME;
```

### 12.3 Cek datafile

Login sebagai DBA/SYS:

```sql
SELECT FILE_NAME, TABLESPACE_NAME, BYTES / 1024 / 1024 AS SIZE_MB, AUTOEXTENSIBLE
FROM DBA_DATA_FILES
WHERE TABLESPACE_NAME = 'VERIDITY_TS';
```

Jika query ini error karena tidak punya privilege, screenshot error tersebut boleh dijadikan bukti bahwa query DBA harus dijalankan dari user admin database.

### 12.4 Cek quota user pada tablespace

Login sebagai DBA/SYS:

```sql
SELECT USERNAME, TABLESPACE_NAME, BYTES / 1024 / 1024 AS USED_MB, MAX_BYTES / 1024 / 1024 AS MAX_MB
FROM DBA_TS_QUOTAS
WHERE USERNAME = 'VERIDITY';
```

### 12.5 Cek privilege user

Login sebagai user aplikasi:

```sql
SELECT * FROM USER_SYS_PRIVS;
SELECT * FROM USER_ROLE_PRIVS;
```

Login sebagai DBA/SYS:

```sql
SELECT * FROM DBA_SYS_PRIVS WHERE GRANTEE = 'VERIDITY';
SELECT * FROM DBA_ROLE_PRIVS WHERE GRANTEE = 'VERIDITY';
```

Privilege minimal yang dicari:

- `CREATE SESSION`
- `CREATE TABLE`
- `CREATE SEQUENCE`
- `CREATE VIEW`
- `CREATE PROCEDURE`

### 12.6 Cek tabel hasil migrasi

Login sebagai user aplikasi:

```sql
SELECT TABLE_NAME
FROM USER_TABLES
WHERE TABLE_NAME IN (
    'USERS',
    'FORENSIC_ANALYSES',
    'CATEGORIES',
    'PRODUCTS',
    'ORDERS',
    'ORDER_ITEMS',
    'VOUCHERS'
)
ORDER BY TABLE_NAME;
```

### 12.7 Cek kolom tabel

Contoh untuk tabel `ORDERS`:

```sql
SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, NULLABLE
FROM USER_TAB_COLUMNS
WHERE TABLE_NAME = 'ORDERS'
ORDER BY COLUMN_ID;
```

Ganti `ORDERS` dengan tabel lain, misalnya `PRODUCTS` atau `FORENSIC_ANALYSES`.

### 12.8 Cek primary key, foreign key, unique constraint

```sql
SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE, TABLE_NAME, STATUS
FROM USER_CONSTRAINTS
WHERE TABLE_NAME IN (
    'USERS',
    'FORENSIC_ANALYSES',
    'CATEGORIES',
    'PRODUCTS',
    'ORDERS',
    'ORDER_ITEMS',
    'VOUCHERS'
)
ORDER BY TABLE_NAME, CONSTRAINT_TYPE;
```

Keterangan `CONSTRAINT_TYPE`:

- `P`: primary key
- `R`: foreign key
- `U`: unique
- `C`: check/not null

Detail kolom constraint:

```sql
SELECT c.TABLE_NAME, c.CONSTRAINT_NAME, c.CONSTRAINT_TYPE, col.COLUMN_NAME
FROM USER_CONSTRAINTS c
JOIN USER_CONS_COLUMNS col ON col.CONSTRAINT_NAME = c.CONSTRAINT_NAME
WHERE c.TABLE_NAME IN (
    'USERS',
    'FORENSIC_ANALYSES',
    'CATEGORIES',
    'PRODUCTS',
    'ORDERS',
    'ORDER_ITEMS',
    'VOUCHERS'
)
ORDER BY c.TABLE_NAME, c.CONSTRAINT_NAME, col.POSITION;
```

### 12.9 Cek index

```sql
SELECT INDEX_NAME, TABLE_NAME, UNIQUENESS, STATUS
FROM USER_INDEXES
WHERE TABLE_NAME IN (
    'USERS',
    'FORENSIC_ANALYSES',
    'CATEGORIES',
    'PRODUCTS',
    'ORDERS',
    'ORDER_ITEMS',
    'VOUCHERS'
)
ORDER BY TABLE_NAME, INDEX_NAME;
```

Detail kolom index:

```sql
SELECT INDEX_NAME, TABLE_NAME, COLUMN_NAME, COLUMN_POSITION
FROM USER_IND_COLUMNS
WHERE TABLE_NAME IN (
    'USERS',
    'FORENSIC_ANALYSES',
    'CATEGORIES',
    'PRODUCTS',
    'ORDERS',
    'ORDER_ITEMS',
    'VOUCHERS'
)
ORDER BY TABLE_NAME, INDEX_NAME, COLUMN_POSITION;
```

### 12.10 Cek data demo

```sql
SELECT COUNT(*) AS TOTAL_USERS FROM USERS;
SELECT COUNT(*) AS TOTAL_ANALYSES FROM FORENSIC_ANALYSES;
SELECT COUNT(*) AS TOTAL_PRODUCTS FROM PRODUCTS;
SELECT COUNT(*) AS TOTAL_ORDERS FROM ORDERS;
SELECT COUNT(*) AS TOTAL_ORDER_ITEMS FROM ORDER_ITEMS;
SELECT COUNT(*) AS TOTAL_VOUCHERS FROM VOUCHERS;
```

### 12.11 Cek relasi data order

```sql
SELECT
    o.ORDER_ID_STRING,
    u.NAME AS USER_NAME,
    p.NAME AS PRODUCT_NAME,
    o.TOTAL_AMOUNT,
    o.PAYMENT_STATUS,
    o.ORDER_STATUS
FROM ORDERS o
JOIN USERS u ON u.ID = o.USER_ID
JOIN PRODUCTS p ON p.ID = o.PRODUCT_ID
ORDER BY o.CREATED_AT DESC;
```

### 12.12 Cek relasi data analisis

```sql
SELECT
    fa.ID,
    u.NAME AS USER_NAME,
    fa.IMAGE_NAME,
    fa.ELA_SCORE,
    fa.IS_DEEPFAKE,
    fa.REPORT_STATUS,
    fa.CREATED_AT
FROM FORENSIC_ANALYSES fa
JOIN USERS u ON u.ID = fa.USER_ID
ORDER BY fa.CREATED_AT DESC;
```
