# PANDUAN DEPLOYMENT AWS EC2 - VERIDITY

Panduan ini untuk deploy semua komponen VERIDITY ke AWS EC2, kecuali `veridity_mobile`.

Komponen yang dideploy:

- `veridity-laravel`: website utama dan API.
- `distri`: website distributor/toko.
- `python`: engine analisis foto/dokumen dan PDF report.
- PostgreSQL: database untuk kebutuhan mata kuliah Framework.
- Amazon S3: penyimpanan file upload foto, dokumen, report PDF, foto profil, produk, dan nota.

## 1. Arsitektur Deployment

Rekomendasi arsitektur:

```text
Internet
  |
  |-- Nginx
        |
        |-- veridity-laravel   http://127.0.0.1:9000 atau php-fpm pool
        |-- distri             http://127.0.0.1:9001 atau php-fpm pool
        |-- python FastAPI     http://127.0.0.1:8001
        |
        |-- PostgreSQL         127.0.0.1:5432
        |-- Amazon S3          file upload dan report
```

Contoh domain:

- `https://veridity.domain.com` untuk `veridity-laravel`.
- `https://distri.domain.com` untuk `distri`.

Jika belum memakai domain, gunakan public IP EC2:

- `http://PUBLIC_IP` untuk VERIDITY.
- `http://PUBLIC_IP:8080` atau subpath Nginx untuk DISTRI.

## 2. Buat Resource AWS

### 2.1 EC2 Instance

Rekomendasi awal:

- OS: Ubuntu Server 22.04 LTS atau 24.04 LTS.
- Instance: minimal `t3.medium` jika Python OCR/analisis cukup berat.
- Storage: minimal 30 GB.
- Security Group:
  - SSH: port `22`, batasi ke IP pribadi jika bisa.
  - HTTP: port `80`.
  - HTTPS: port `443`.
  - Jangan buka PostgreSQL `5432` ke publik.
  - Jangan buka FastAPI `8001` ke publik.

### 2.2 S3 Bucket

Buat bucket misalnya:

```text
veridity-prod-storage
```

Struktur folder yang disarankan:

```text
forensics/
reports/
profile-photos/
products/
proofs/
results/
```

Rekomendasi akses:

- Bucket tidak perlu public penuh.
- Laravel mengambil file melalui temporary URL atau URL S3 yang dikontrol.
- Untuk tugas/demo sederhana, boleh memakai object public-read, tetapi lebih aman tetap private.

### 2.3 IAM User atau IAM Role

Lebih aman memakai IAM Role yang ditempel ke EC2. Jika ingin sederhana, bisa memakai IAM User dengan access key.

Policy minimal S3:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::veridity-prod-storage",
        "arn:aws:s3:::veridity-prod-storage/*"
      ]
    }
  ]
}
```

## 3. Setup Server EC2

Login SSH:

```bash
ssh -i key.pem ubuntu@PUBLIC_IP
```

Update server:

```bash
sudo apt update
sudo apt upgrade -y
```

Install package dasar:

```bash
sudo apt install -y nginx git unzip curl supervisor
```

Install PHP dan extension umum:

```bash
sudo apt install -y php php-cli php-fpm php-mbstring php-xml php-curl php-zip php-bcmath php-gd php-pgsql php-intl
```

Install Composer:

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

Install Node.js:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Install PostgreSQL:

```bash
sudo apt install -y postgresql postgresql-contrib
```

Install Python tools:

```bash
sudo apt install -y python3 python3-venv python3-pip
```

Install Tesseract:

```bash
sudo apt install -y tesseract-ocr
```

## 4. Setup PostgreSQL

Masuk PostgreSQL:

```bash
sudo -u postgres psql
```

Buat database dan user:

```sql
CREATE USER veridity_user WITH PASSWORD 'password_kuat';
CREATE DATABASE veridity_framework OWNER veridity_user;
CREATE DATABASE distri_framework OWNER veridity_user;
GRANT ALL PRIVILEGES ON DATABASE veridity_framework TO veridity_user;
GRANT ALL PRIVILEGES ON DATABASE distri_framework TO veridity_user;
\q
```

## 5. Upload/Clone Project

Contoh lokasi project:

```bash
sudo mkdir -p /var/www/veridity
sudo chown -R ubuntu:www-data /var/www/veridity
cd /var/www/veridity
git clone REPOSITORY_URL .
```

Struktur:

```text
/var/www/veridity/veridity-laravel
/var/www/veridity/distri
/var/www/veridity/python
```

Jika tidak memakai git, upload project melalui SCP/SFTP.

## 6. Setup `veridity-laravel`

```bash
cd /var/www/veridity/veridity-laravel
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Contoh `.env` production:

```env
APP_NAME=VERIDITY
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://veridity.domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=veridity_framework
DB_USERNAME=veridity_user
DB_PASSWORD=password_kuat

PYTHON_ENGINE_URL=http://127.0.0.1:8001
PYTHON_PATH=/var/www/veridity/python/.venv/bin/python
PYTHON_TOOLKIT_SCRIPT=/var/www/veridity/python/analyze_all.py
TESSERACT_PATH=/usr/bin/tesseract

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=isi_jika_tidak_pakai_iam_role
AWS_SECRET_ACCESS_KEY=isi_jika_tidak_pakai_iam_role
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=veridity-prod-storage
AWS_URL=https://veridity-prod-storage.s3.ap-southeast-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

VERIDITY_INTEGRATION_KEY=veridity-distri-production-key
VERIDITY_INTEGRATION_USER_ID=1
```

Install package S3 Laravel jika belum ada:

```bash
composer require league/flysystem-aws-s3-v3
```

Jalankan migrasi:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permission:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 7. Setup `distri`

```bash
cd /var/www/veridity/distri
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Contoh `.env` production:

```env
APP_NAME=DISTRI
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://distri.domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=distri_framework
DB_USERNAME=veridity_user
DB_PASSWORD=password_kuat

VERIDITY_BASE_URL=https://veridity.domain.com
VERIDITY_INTEGRATION_KEY=veridity-distri-production-key

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=isi_jika_tidak_pakai_iam_role
AWS_SECRET_ACCESS_KEY=isi_jika_tidak_pakai_iam_role
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=veridity-prod-storage
AWS_URL=https://veridity-prod-storage.s3.ap-southeast-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Install package S3 Laravel jika belum ada:

```bash
composer require league/flysystem-aws-s3-v3
```

Jalankan migrasi:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permission:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 8. Setup Python Engine

```bash
cd /var/www/veridity/python
python3 -m venv .venv
source .venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
```

Test manual:

```bash
uvicorn main_api:app --host 127.0.0.1 --port 8001
```

Jika sudah berjalan, hentikan lalu buat service Supervisor.

## 9. Supervisor untuk Python FastAPI

Buat file:

```bash
sudo nano /etc/supervisor/conf.d/veridity-python.conf
```

Isi:

```ini
[program:veridity-python]
directory=/var/www/veridity/python
command=/var/www/veridity/python/.venv/bin/uvicorn main_api:app --host 127.0.0.1 --port 8001
autostart=true
autorestart=true
stderr_logfile=/var/log/veridity-python.err.log
stdout_logfile=/var/log/veridity-python.out.log
user=ubuntu
environment=PYTHONUNBUFFERED="1"
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start veridity-python
sudo supervisorctl status
```

## 10. Nginx untuk `veridity-laravel`

Buat config:

```bash
sudo nano /etc/nginx/sites-available/veridity
```

Isi:

```nginx
server {
    listen 80;
    server_name veridity.domain.com;
    root /var/www/veridity/veridity-laravel/public;

    index index.php index.html;
    client_max_body_size 30M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Catatan: sesuaikan path `php-fpm.sock` dengan versi PHP server, misalnya `/run/php/php8.3-fpm.sock`.

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/veridity /etc/nginx/sites-enabled/veridity
sudo nginx -t
sudo systemctl reload nginx
```

## 11. Nginx untuk `distri`

Buat config:

```bash
sudo nano /etc/nginx/sites-available/distri
```

Isi:

```nginx
server {
    listen 80;
    server_name distri.domain.com;
    root /var/www/veridity/distri/public;

    index index.php index.html;
    client_max_body_size 30M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/distri /etc/nginx/sites-enabled/distri
sudo nginx -t
sudo systemctl reload nginx
```

## 12. HTTPS dengan Certbot

Install:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Aktifkan HTTPS:

```bash
sudo certbot --nginx -d veridity.domain.com
sudo certbot --nginx -d distri.domain.com
```

## 13. Catatan Penting S3 untuk Foto dan File Upload

Config S3 Laravel sudah ada di:

- `veridity-laravel/config/filesystems.php`
- `distri/config/filesystems.php`

Namun beberapa bagian kode saat ini masih memakai disk lokal/public secara eksplisit, misalnya:

- `Storage::disk('public')`
- `asset('storage/...')`
- `public_path('products')`
- `public_path('proofs')`

Agar semua foto benar-benar memakai S3, upload perlu diarahkan ke disk `s3`.

### 13.1 Target perubahan di `veridity-laravel`

Bagian yang perlu memakai S3:

- Upload foto/dokumen analisis: folder `forensics/`.
- Foto profil: folder `profile-photos/`.
- Hasil ELA/noise: folder `results/`.
- PDF report: folder `reports/`.

Pola kode yang disarankan:

```php
$disk = config('filesystems.default', 'public');
$path = $request->file('image')->store('forensics', $disk);
$url = Storage::disk($disk)->url($path);
```

Untuk download file private, lebih aman memakai temporary URL:

```php
$url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));
```

### 13.2 Target perubahan di `distri`

Bagian yang perlu memakai S3:

- Foto produk: folder `products/`.
- Bukti nota/payment proof: folder `proofs/`.

Pola upload produk:

```php
$disk = config('filesystems.default', 'public');
$path = $request->file('image')->store('products', $disk);
```

Pola upload nota:

```php
$disk = config('filesystems.default', 'public');
$path = $request->file('proof_of_transfer')->store('proofs', $disk);
```

Database sebaiknya menyimpan path S3 seperti:

```text
products/namafile.jpg
proofs/namafile.jpg
forensics/namafile.jpg
```

Bukan hanya nama file saja.

## 14. Jika S3 Belum Diubah di Kode

Jika deploy dilakukan sebelum kode upload diganti ke S3:

- File upload `veridity-laravel` masih masuk ke `storage/app/public`.
- File produk dan nota `distri` masih masuk ke `public/products` dan `public/proofs`.
- S3 env tidak otomatis bekerja untuk bagian yang masih hardcoded ke lokal.

Untuk demo AWS dengan S3, pastikan kode upload sudah memakai:

```php
Storage::disk(config('filesystems.default'))
```

atau langsung:

```php
Storage::disk('s3')
```

## 15. Queue dan Scheduler

Jika aplikasi memakai queue:

```bash
php artisan queue:work
```

Buat Supervisor per aplikasi jika queue dibutuhkan.

Cron scheduler:

```bash
crontab -e
```

Isi:

```cron
* * * * * cd /var/www/veridity/veridity-laravel && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/veridity/distri && php artisan schedule:run >> /dev/null 2>&1
```

## 16. Checklist Verifikasi Deployment

`veridity-laravel`:

- [ ] Halaman login/register terbuka.
- [ ] User dapat upload foto.
- [ ] User dapat upload dokumen PDF.
- [ ] Python engine mengembalikan hasil.
- [ ] Riwayat analisis muncul.
- [ ] PDF report dapat diunduh.
- [ ] File upload tersimpan di S3.

`distri`:

- [ ] Reseller dapat login/register.
- [ ] Katalog produk tampil.
- [ ] Keranjang dan voucher berjalan.
- [ ] Checkout berjalan.
- [ ] Upload nota tersimpan di S3.
- [ ] Validasi nota memanggil VERIDITY.
- [ ] Admin dapat kelola produk, pantau pesanan, dan validasi nota.

`python`:

- [ ] Service Supervisor status `RUNNING`.
- [ ] Endpoint FastAPI dapat dipanggil dari EC2 localhost.
- [ ] Tesseract tersedia di `/usr/bin/tesseract`.

PostgreSQL:

- [ ] Database `veridity_framework` ada.
- [ ] Database `distri_framework` ada.
- [ ] Migrasi Laravel berhasil.

Nginx/HTTPS:

- [ ] `nginx -t` sukses.
- [ ] Domain VERIDITY aktif.
- [ ] Domain DISTRI aktif.
- [ ] HTTPS aktif.

## 17. Perintah Troubleshooting

Cek log Laravel:

```bash
tail -f /var/www/veridity/veridity-laravel/storage/logs/laravel.log
tail -f /var/www/veridity/distri/storage/logs/laravel.log
```

Cek log Python:

```bash
tail -f /var/log/veridity-python.out.log
tail -f /var/log/veridity-python.err.log
```

Cek Nginx:

```bash
sudo nginx -t
sudo tail -f /var/log/nginx/error.log
```

Cek service:

```bash
sudo systemctl status nginx
sudo systemctl status php*-fpm
sudo supervisorctl status
```

Cek PostgreSQL:

```bash
sudo -u postgres psql
\l
\q
```

## 18. Catatan untuk Flutter

`veridity_mobile` tidak dideploy ke EC2. Setelah website/API sudah online, Flutter cukup diarahkan ke base URL:

```bash
flutter run --dart-define=VERIDITY_API_BASE_URL=https://veridity.domain.com/api
```

Jika build APK:

```bash
flutter build apk --release --dart-define=VERIDITY_API_BASE_URL=https://veridity.domain.com/api
```
