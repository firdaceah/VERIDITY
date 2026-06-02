# PANDUAN DEPLOYMENT AWS EC2 - VERIDITY

Panduan ini untuk deploy semua komponen VERIDITY ke AWS EC2, kecuali `veridity_mobile`.

Komponen yang dideploy:

- `veridity-laravel`: website utama dan API.
- `distri`: website distributor/toko.
- `python`: engine analisis foto/dokumen dan PDF report.
- PostgreSQL: database untuk kebutuhan mata kuliah Framework.
- Amazon S3: penyimpanan file upload foto, dokumen, report PDF, foto profil, produk, dan nota.

## 0. Gambaran untuk Pemula

Kalau ini pertama kali memakai AWS, urutan besarnya adalah:

1. Buat S3 bucket untuk menyimpan file upload.
2. Buat IAM Role agar EC2 boleh akses S3.
3. Buat EC2 instance Ubuntu.
4. Hubungkan IAM Role ke EC2.
5. Login ke EC2 lewat SSH.
6. Install Nginx, PHP, Composer, Node.js, PostgreSQL, Python, dan Tesseract.
7. Upload/clone project ke EC2.
8. Isi `.env` untuk `veridity-laravel` dan `distri`.
9. Jalankan migration PostgreSQL.
10. Jalankan Python FastAPI dengan Supervisor.
11. Hubungkan website ke Nginx.
12. Aktifkan HTTPS jika sudah punya domain.
13. Test upload foto/dokumen dan cek file masuk S3.

Catatan biaya:

- EC2, S3, dan data transfer dapat menimbulkan biaya.
- Untuk demo, matikan instance jika tidak dipakai.
- Jangan share access key, `.env`, private key `.pem`, atau password database.

## 0.1 Persiapan Lokal Sebelum Masuk AWS

Siapkan:

- Akun AWS yang bisa login ke AWS Console.
- Project VERIDITY sudah ada di GitHub/GitLab atau siap di-upload via SFTP.
- Domain opsional, misalnya `veridity.domain.com` dan `distri.domain.com`.
- Laptop Windows dengan PowerShell.
- File private key `.pem` nanti akan di-download dari AWS dan disimpan aman.

Jika belum punya domain, tetap bisa pakai public IP EC2 terlebih dahulu.

## 0.2 Pilihan Region AWS

Pilih region terdekat, misalnya:

```text
Asia Pacific (Singapore) / ap-southeast-1
```

Gunakan region yang sama untuk:

- EC2
- S3
- IAM Role policy yang mengarah ke bucket

S3 bucket namanya harus unik secara global, jadi nama seperti `veridity-storage` biasanya sudah dipakai orang lain. Gunakan nama unik, misalnya:

```text
veridity-prod-storage-nama-kamu
```

## 0.3 Buat S3 Bucket Lewat AWS Console

Langkah:

1. Login ke AWS Console.
2. Di search bar atas, ketik `S3`.
3. Buka service `S3`.
4. Klik `Create bucket`.
5. Isi:
   - Bucket name: `veridity-prod-storage-nama-kamu`
   - AWS Region: `ap-southeast-1` atau region yang kamu pilih
6. Object Ownership:
   - Pilih default `ACLs disabled`.
7. Block Public Access:
   - Untuk mode aman, biarkan semua centang aktif.
   - Artinya bucket private, aplikasi yang mengambil file.
8. Bucket Versioning:
   - Boleh `Disable` untuk demo.
9. Default encryption:
   - Biarkan default Amazon S3 managed keys.
10. Klik `Create bucket`.

Setelah bucket dibuat, buat folder agar rapi:

1. Masuk ke bucket.
2. Klik `Create folder`.
3. Buat folder:
   - `forensics`
   - `reports`
   - `profile-photos`
   - `products`
   - `proofs`
   - `results`

Folder di S3 sebenarnya hanya prefix object, tetapi membuat folder ini membantu saat demo.

## 0.4 Buat IAM Policy untuk Akses S3

IAM Policy menentukan izin apa yang boleh dilakukan EC2 ke bucket S3.

Langkah:

1. Di search bar AWS Console, ketik `IAM`.
2. Buka `IAM`.
3. Pilih menu `Policies`.
4. Klik `Create policy`.
5. Pilih tab `JSON`.
6. Masukkan policy berikut, ganti nama bucket:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::veridity-prod-storage-nama-kamu/*"
    },
    {
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket"
      ],
      "Resource": "arn:aws:s3:::veridity-prod-storage-nama-kamu"
    }
  ]
}
```

7. Klik `Next`.
8. Policy name:

```text
VeridityS3StoragePolicy
```

9. Klik `Create policy`.

## 0.5 Buat IAM Role untuk EC2

Role ini ditempel ke EC2 agar Laravel dapat akses S3 tanpa menyimpan access key di `.env`.

Langkah:

1. Buka `IAM`.
2. Pilih menu `Roles`.
3. Klik `Create role`.
4. Trusted entity type:
   - Pilih `AWS service`.
5. Use case:
   - Pilih `EC2`.
6. Klik `Next`.
7. Cari policy:

```text
VeridityS3StoragePolicy
```

8. Centang policy tersebut.
9. Klik `Next`.
10. Role name:

```text
VeridityEC2Role
```

11. Klik `Create role`.

## 0.6 Buat Key Pair EC2

Key pair dipakai untuk SSH ke server.

Langkah:

1. Buka service `EC2`.
2. Di sidebar kiri, cari `Network & Security`.
3. Klik `Key Pairs`.
4. Klik `Create key pair`.
5. Isi:
   - Name: `veridity-key`
   - Key pair type: `RSA`
   - Private key file format: `.pem`
6. Klik `Create key pair`.
7. File `veridity-key.pem` akan ter-download.
8. Simpan file ini di folder aman, misalnya:

```text
C:\Users\user\Downloads\veridity-key.pem
```

Jangan kirim file `.pem` ke siapa pun dan jangan upload ke GitHub.

## 0.7 Buat Security Group

Security Group adalah firewall EC2.

Langkah:

1. Buka service `EC2`.
2. Sidebar kiri, klik `Security Groups`.
3. Klik `Create security group`.
4. Isi:
   - Security group name: `veridity-web-sg`
   - Description: `Security group for VERIDITY web server`
   - VPC: biarkan default
5. Inbound rules:

| Type | Port | Source | Keterangan |
| --- | --- | --- | --- |
| SSH | 22 | My IP | Untuk login server |
| HTTP | 80 | Anywhere IPv4 | Website tanpa HTTPS |
| HTTPS | 443 | Anywhere IPv4 | Website HTTPS |

6. Jangan buka:
   - PostgreSQL `5432`
   - Python/FastAPI `8001`
7. Klik `Create security group`.

Kalau IP internet kamu berubah dan SSH gagal, edit inbound rule SSH dan pilih `My IP` lagi.

## 0.8 Launch EC2 Instance

Langkah:

1. Buka service `EC2`.
2. Klik `Instances`.
3. Klik `Launch instances`.
4. Name:

```text
veridity-server
```

5. Application and OS Images:
   - Pilih `Ubuntu`.
   - Rekomendasi: Ubuntu Server 22.04 LTS atau 24.04 LTS.
6. Instance type:
   - Minimal: `t3.medium`.
   - Jika ingin hemat untuk percobaan awal: `t3.small`, tetapi Python bisa lebih lambat.
7. Key pair:
   - Pilih `veridity-key`.
8. Network settings:
   - Pilih security group existing: `veridity-web-sg`.
9. Configure storage:
   - Minimal 30 GB.
10. Advanced details:
   - IAM instance profile: pilih `VeridityEC2Role`.
11. Klik `Launch instance`.

Tunggu instance status menjadi:

```text
Instance state: Running
Status checks: 2/2 checks passed
```

## 0.9 Ambil Public IP EC2

Langkah:

1. Buka `EC2 > Instances`.
2. Klik instance `veridity-server`.
3. Copy `Public IPv4 address`.

Contoh:

```text
13.250.10.20
```

IP ini dipakai untuk SSH dan akses web sementara.

## 0.10 Login SSH dari Windows PowerShell

Buka PowerShell di folder tempat file `.pem` berada.

```powershell
cd C:\Users\user\Downloads
```

Ubah permission file key:

```powershell
icacls .\veridity-key.pem /inheritance:r
icacls .\veridity-key.pem /grant:r "$($env:USERNAME):(R)"
```

Login:

```powershell
ssh -i .\veridity-key.pem ubuntu@PUBLIC_IP
```

Contoh:

```powershell
ssh -i .\veridity-key.pem ubuntu@13.250.10.20
```

Jika muncul pertanyaan fingerprint:

```text
Are you sure you want to continue connecting?
```

Ketik:

```text
yes
```

Jika berhasil, prompt berubah menjadi server Ubuntu:

```text
ubuntu@ip-xxx-xxx-xxx:~$
```

## 0.11 Cek IAM Role dan S3 dari EC2

Setelah login SSH, install AWS CLI:

```bash
sudo apt update
sudo apt install -y awscli
```

Cek identity:

```bash
aws sts get-caller-identity
```

Cek akses bucket:

```bash
aws s3 ls s3://veridity-prod-storage-nama-kamu
```

Jika berhasil, berarti IAM Role EC2 sudah bisa mengakses S3.

## 0.12 Jika Belum Punya Repository Git

Ada dua cara upload project:

Cara A, lewat GitHub/GitLab:

```bash
cd /var/www/veridity
git clone REPOSITORY_URL .
```

Cara B, lewat SCP dari Windows:

```powershell
scp -i .\veridity-key.pem -r C:\Users\user\PENS\Semester-4\VERIDITY ubuntu@PUBLIC_IP:/home/ubuntu/VERIDITY
```

Lalu di EC2:

```bash
sudo mkdir -p /var/www/veridity
sudo cp -r /home/ubuntu/VERIDITY/* /var/www/veridity/
sudo chown -R ubuntu:www-data /var/www/veridity
```

Git lebih disarankan karena lebih mudah update project.

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

## 3.1 Cek Versi Setelah Install

Jalankan:

```bash
php -v
composer --version
node -v
npm -v
python3 --version
psql --version
tesseract --version
nginx -v
```

Jika ada command yang `not found`, berarti package tersebut belum terinstall.

## 3.2 Buat Folder Project di Server

```bash
sudo mkdir -p /var/www/veridity
sudo chown -R ubuntu:www-data /var/www/veridity
sudo chmod -R 775 /var/www/veridity
```

Masuk folder:

```bash
cd /var/www/veridity
```

Jika memakai Git:

```bash
git clone REPOSITORY_URL .
```

Jika upload via SCP ke `/home/ubuntu/VERIDITY`, salin ke `/var/www/veridity`:

```bash
sudo cp -r /home/ubuntu/VERIDITY/* /var/www/veridity/
sudo chown -R ubuntu:www-data /var/www/veridity
```

Pastikan folder ada:

```bash
ls -la /var/www/veridity
```

Harus terlihat:

```text
veridity-laravel
distri
python
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

## 4.1 Test Login PostgreSQL

Jalankan:

```bash
psql -h 127.0.0.1 -U veridity_user -d veridity_framework
```

Masukkan password.

Jika berhasil, keluar:

```sql
\q
```

Ulangi untuk database `distri_framework`:

```bash
psql -h 127.0.0.1 -U veridity_user -d distri_framework
```

Jika muncul error authentication, cek ulang password dan user PostgreSQL.

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

Jika tidak ada `.env.example`, buat `.env` manual:

```bash
nano .env
```

Untuk edit `.env` jika sudah ada:

```bash
nano .env
```

Tips editor `nano`:

- Simpan: `Ctrl + O`, lalu `Enter`.
- Keluar: `Ctrl + X`.

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

Jika memakai IAM Role EC2, `AWS_ACCESS_KEY_ID` dan `AWS_SECRET_ACCESS_KEY` boleh dikosongkan atau tidak ditulis. Laravel/AWS SDK akan mengambil credential dari IAM Role.

Untuk IAM Role, bagian S3 cukup seperti ini:

```env
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=veridity-prod-storage-nama-kamu
AWS_URL=https://veridity-prod-storage-nama-kamu.s3.ap-southeast-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Jalankan migrasi:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika migrate gagal karena tabel belum ada/permission:

```bash
php artisan config:clear
php artisan migrate --force -vvv
```

Cek apakah koneksi database terbaca:

```bash
php artisan tinker
```

Lalu di dalam tinker:

```php
DB::connection()->getPdo();
exit
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

Edit `.env`:

```bash
nano .env
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

Jika memakai IAM Role EC2, S3 env cukup:

```env
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=veridity-prod-storage-nama-kamu
AWS_URL=https://veridity-prod-storage-nama-kamu.s3.ap-southeast-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
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

Jika `requirements.txt` tidak ada atau install gagal, cek file dependency di folder `python`:

```bash
ls -la
```

Untuk melihat error dependency:

```bash
pip install -r requirements.txt -v
```

Test manual:

```bash
uvicorn main_api:app --host 127.0.0.1 --port 8001
```

Buka terminal SSH kedua, lalu test:

```bash
curl http://127.0.0.1:8001/
```

Jika ada response JSON/health, Python engine berjalan.

Kembali ke terminal pertama, tekan `Ctrl + C` untuk mematikan uvicorn manual.

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

Cara cek socket PHP-FPM:

```bash
ls /run/php/
```

Jika muncul:

```text
php8.3-fpm.sock
```

maka gunakan:

```nginx
fastcgi_pass unix:/run/php/php8.3-fpm.sock;
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/veridity /etc/nginx/sites-enabled/veridity
sudo nginx -t
sudo systemctl reload nginx
```

Jika belum punya domain dan ingin test pakai public IP, ubah:

```nginx
server_name veridity.domain.com;
```

menjadi:

```nginx
server_name _;
```

Lalu akses:

```text
http://PUBLIC_IP
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

Jika belum punya domain, ada dua pilihan.

Pilihan A: gunakan port `8080`.

Ubah blok server `distri`:

```nginx
server {
    listen 8080;
    server_name _;
    root /var/www/veridity/distri/public;

    index index.php index.html;
    client_max_body_size 30M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Lalu buka Security Group EC2 dan tambahkan inbound rule:

| Type | Port | Source |
| --- | --- | --- |
| Custom TCP | 8080 | Anywhere IPv4 |

Akses:

```text
http://PUBLIC_IP:8080
```

Pilihan B: gunakan domain/subdomain.

Jika sudah punya domain, arahkan DNS:

```text
veridity.domain.com -> PUBLIC_IP
distri.domain.com   -> PUBLIC_IP
```

Lalu gunakan config Nginx dengan `server_name` masing-masing.

Untuk produksi, pilihan domain/subdomain lebih rapi.

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

Certbot hanya bisa dipakai jika domain sudah mengarah ke public IP EC2.

Cara cek DNS dari laptop:

```bash
nslookup veridity.domain.com
nslookup distri.domain.com
```

Hasilnya harus mengarah ke public IP EC2.

Jika belum punya domain, lewati HTTPS dulu dan pakai HTTP untuk demo awal.

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

## 13.3 Checklist perubahan kode agar S3 benar-benar aktif

Sebelum deploy final, cek kode berikut.

Di `veridity-laravel`, cari:

```bash
rg "Storage::disk\\('public'\\)|asset\\('storage|storage_path\\('app/public|public_path" app resources
```

Target perubahan:

- Upload file memakai `Storage::disk(config('filesystems.default'))`.
- URL file memakai `Storage::disk(config('filesystems.default'))->url($path)` atau temporary URL S3.
- Path lokal hanya dipakai jika Python membutuhkan file fisik sementara.

Karena Python image analysis membaca file dari filesystem lokal, pola aman adalah:

1. Upload file ke S3.
2. Download/copy sementara ke `/tmp/veridity/...` untuk dianalisis Python.
3. Hasil report/visual di-upload lagi ke S3.
4. File sementara dihapus.

Contoh pola:

```php
$disk = config('filesystems.default', 'public');
$path = $request->file('image')->store('forensics', $disk);

$temporaryLocalPath = storage_path('app/tmp/'.basename($path));
Storage::disk($disk)->get($path);
file_put_contents($temporaryLocalPath, Storage::disk($disk)->get($path));

// Kirim $temporaryLocalPath ke Python.
```

Di `distri`, cari:

```bash
rg "public_path\\('products|public_path\\('proofs|asset\\('products|asset\\('proofs" app resources
```

Target perubahan:

- Foto produk upload ke S3 folder `products/`.
- Bukti nota upload ke S3 folder `proofs/`.
- Preview gambar memakai URL dari S3.

Contoh:

```php
$disk = config('filesystems.default', 'public');
$path = $request->file('proof_of_transfer')->store('proofs', $disk);
```

Database menyimpan:

```text
proofs/nama-file.jpg
```

Bukan:

```text
nama-file.jpg
```

## 13.4 Test Upload ke S3 dari Laravel

Masuk folder `veridity-laravel`:

```bash
cd /var/www/veridity/veridity-laravel
php artisan tinker
```

Di tinker:

```php
Storage::disk('s3')->put('test/veridity.txt', 'hello veridity');
Storage::disk('s3')->exists('test/veridity.txt');
exit
```

Jika hasil `exists` adalah `true`, Laravel bisa menulis ke S3.

Cek juga di AWS Console:

1. Buka S3.
2. Buka bucket.
3. Cek folder `test`.
4. Harus ada file `veridity.txt`.

Hapus file test:

```bash
php artisan tinker
```

```php
Storage::disk('s3')->delete('test/veridity.txt');
exit
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

## 19. Urutan Eksekusi Cepat dari Nol

Gunakan checklist ini saat praktik:

1. AWS Console:
   - [ ] Buat S3 bucket.
   - [ ] Buat IAM policy S3.
   - [ ] Buat IAM role EC2.
   - [ ] Buat key pair.
   - [ ] Buat security group.
   - [ ] Launch EC2 Ubuntu.
   - [ ] Attach IAM role ke EC2.

2. SSH:
   - [ ] Login ke EC2 dari PowerShell.
   - [ ] Install package server.
   - [ ] Test `aws s3 ls`.
   - [ ] Buat PostgreSQL user dan database.

3. Project:
   - [ ] Upload/clone project ke `/var/www/veridity`.
   - [ ] Setup `veridity-laravel`.
   - [ ] Setup `distri`.
   - [ ] Setup `python`.

4. Environment:
   - [ ] Isi `.env` `veridity-laravel`.
   - [ ] Isi `.env` `distri`.
   - [ ] Pastikan `VERIDITY_INTEGRATION_KEY` sama di dua aplikasi.
   - [ ] Pastikan `PYTHON_ENGINE_URL=http://127.0.0.1:8001`.
   - [ ] Pastikan `FILESYSTEM_DISK=s3`.

5. Build dan database:
   - [ ] `composer install`.
   - [ ] `npm install`.
   - [ ] `npm run build`.
   - [ ] `php artisan migrate --force`.

6. Service:
   - [ ] Python FastAPI jalan di Supervisor.
   - [ ] Nginx config VERIDITY aktif.
   - [ ] Nginx config DISTRI aktif.
   - [ ] `nginx -t` sukses.

7. Test:
   - [ ] Buka VERIDITY dari browser.
   - [ ] Buka DISTRI dari browser.
   - [ ] Login/register.
   - [ ] Upload foto.
   - [ ] Upload dokumen.
   - [ ] Checkout DISTRI.
   - [ ] Upload nota.
   - [ ] Cek file masuk S3.

## 20. File yang Tidak Boleh Diupload ke GitHub

Pastikan file ini tidak masuk repository:

```text
.env
*.pem
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
node_modules/
vendor/
.venv/
```

Jika memakai git, cek:

```bash
git status
```

Jangan commit credential AWS, password database, atau private key.
