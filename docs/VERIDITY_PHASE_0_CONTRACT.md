# VERIDITY Phase 0 Contract

Dokumen ini mengunci kontrak awal antar folder sebelum Fase 1 sampai Fase 4 dieksekusi. Tujuannya agar `veridity-laravel`, `veridity_mobile`, `python`, dan `distri` membaca status, endpoint, dan environment dengan arti yang sama.

## Environment

### Laravel API dan Website

Variabel utama berada di `veridity-laravel/.env`.

```env
APP_URL=http://localhost:8000
APP_API_URL=http://localhost:8000/api

PYTHON_ENGINE_URL=http://127.0.0.1:8001
PYTHON_PATH=C:/path/to/python.exe
PYTHON_TOOLKIT_SCRIPT=C:/path/to/VERIDITY/python/analyze_all.py

DISTRIBUTOR_API_KEY=change-me
```

### Profil Framework

Dipakai untuk website Laravel, Flutter, Python engine, `distri`, dan deployment AWS EC2.

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=veridity_db
DB_USERNAME=postgres
DB_PASSWORD=secret
```

### Profil Basis Data

Dipakai untuk demonstrasi Oracle.

```env
DB_CONNECTION=oracle
DB_HOST=127.0.0.1
DB_PORT=1521
DB_DATABASE=orcl
DB_USERNAME=veridity
DB_PASSWORD=secret
```

## Laravel Web Routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/` | Landing page |
| GET | `/register` | Form register |
| POST | `/register` | Submit register |
| GET | `/login` | Form login |
| POST | `/login` | Submit login |
| POST | `/logout` | Logout session |
| GET | `/dashboard` | Dashboard user |
| POST | `/audit/analyze` | Upload dan analisis foto/dokumen dari web |
| POST | `/audit/upload` | Upload gambar legacy |
| GET | `/my-audits` | Riwayat audit user |
| GET | `/audit/result/{id}` | Detail hasil audit |
| DELETE | `/audit/{id}` | Hapus audit |
| GET | `/audit/download-pdf/{id}` | Download report PDF |
| GET | `/admin/dashboard` | Dashboard admin |
| GET | `/admin/audit-logs` | Log audit admin |
| GET | `/admin/audit/{id}` | Detail audit admin |
| GET | `/admin/admin/blacklist` | Daftar fraud/danger |

## Laravel API Routes

Semua endpoint protected memakai Bearer token Sanctum kecuali register dan login.

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/api/register` | No | Register mobile/API |
| POST | `/api/login` | No | Login mobile/API |
| POST | `/api/logout` | Yes | Revoke token |
| GET | `/api/profile` | Yes | Data user login |
| POST | `/api/audits` | Yes | Upload foto/dokumen untuk analisis |
| GET | `/api/audits` | Yes | Riwayat audit user |
| GET | `/api/audits/{id}` | Yes | Detail audit |
| DELETE | `/api/audits/{id}` | Yes | Hapus audit |
| GET | `/api/audits/{id}/report` | Yes | Download report PDF |
| POST | `/api/analyze` | Yes | Alias sementara untuk `/api/audits` |
| GET | `/api/history` | Yes | Alias sementara untuk `/api/audits` |

## Python Engine

Python engine berjalan sebagai service FastAPI di `PYTHON_ENGINE_URL`.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/` | Health check |
| POST | `/analyze-document` | Analisis PDF |
| POST | `/generate-pdf-report` | Generate PDF report dengan highlight |

Untuk citra, Laravel masih memakai `PYTHON_PATH` dan `PYTHON_TOOLKIT_SCRIPT` yang menunjuk ke `python/analyze_all.py`.

## Shared Response Shape

Response sukses API Laravel:

```json
{
  "status": "success",
  "message": "Analisis selesai",
  "data": {},
  "meta": {}
}
```

Response auth API Laravel:

```json
{
  "status": "success",
  "message": "Login berhasil!",
  "data": {
    "id": 1,
    "name": "User",
    "email": "user@example.test"
  },
  "user": {
    "id": 1,
    "name": "User",
    "email": "user@example.test"
  },
  "access_token": "plain-text-token",
  "token": "plain-text-token",
  "token_type": "Bearer"
}
```

`data/access_token` adalah kontrak utama untuk clean architecture Flutter. `user/token` dipertahankan sebagai alias kompatibilitas untuk layar Flutter lama.

Response error API Laravel:

```json
{
  "status": "error",
  "message": "Validasi gagal",
  "errors": {}
}
```

## Shared Analysis Status

Kontrak status disimpan di `veridity-laravel/config/veridity.php`.

| Key | Meaning | UI |
| --- | --- | --- |
| `success` | File terlihat asli atau aman | Hijau |
| `warning` | File mencurigakan, campuran, atau membutuhkan review | Kuning/oranye |
| `danger` | File sangat berbahaya, deepfake, atau AI generated kuat | Merah |
| `error` | Analisis gagal atau service tidak tersedia | Abu-abu/merah error |

## `distri` Payment Contract

Checkout `distri` meniru pola aplikasi ritel seperti Alfagift: user memilih metode pembayaran dulu, lalu mengikuti instruksi channel yang dipilih. Metode yang membutuhkan bukti manual akan meminta upload proof dan proof tersebut dianalisis oleh VERIDITY.

Kontrak awal payment method:

| Key | Channels | Requires Proof |
| --- | --- | --- |
| `bank_transfer` | BCA, BNI, BRI, Mandiri | Yes |
| `virtual_account` | BCA VA, BNI VA, BRI VA, Mandiri VA | Yes |
| `e_wallet` | DANA, OVO, GoPay, ShopeePay | Yes |
| `qris` | QRIS Static Demo | Yes |
| `cod` | COD | No |

Endpoint integrasi target untuk Fase 4:

```http
POST /api/integrations/distri/analyze-proof
X-Veridity-Integration-Key: <DISTRIBUTOR_API_KEY>
```

Multipart fields:

- `proof`
- `order_id`
- `payment_method`
- `payment_channel`
- `source=distri`

## Verification Commands

```bash
cd veridity-laravel
php artisan route:list
php artisan test
```

FastAPI health check:

```bash
curl http://127.0.0.1:8001/
```
