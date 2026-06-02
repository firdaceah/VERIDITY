# REVISI ORACLE - VERIDITY DAN DISTRI

## 1. Keputusan Struktur User Oracle

Tidak perlu menggabungkan user `VERIDITY` dan `DISTRI`.

Rekomendasi:

- `VERIDITY` tetap dipakai oleh project `veridity-laravel`.
- `DISTRI` tetap dipakai oleh project `distri`.
- Keduanya boleh memakai tablespace yang sama, misalnya `VERIDITY_TS`, agar bukti DBA lebih rapi.
- Pemisahan user/schema lebih baik karena tabel VERIDITY dan DISTRI memang berasal dari dua aplikasi berbeda.

Alasan tidak digabung:

- `veridity-laravel` memiliki tabel utama seperti `USERS` dan `FORENSIC_ANALYSES`.
- `distri` memiliki tabel toko seperti `PRODUCTS`, `ORDERS`, `ORDER_ITEMS`, `VOUCHERS`, dan `CATEGORIES`.
- Jika digabung ke satu user, nama tabel seperti `USERS` bisa bentrok karena kedua aplikasi punya tabel `USERS`.
- Pemisahan schema memudahkan demo: VERIDITY untuk forensik, DISTRI untuk toko/distributor.

## 2. Masalah dari Hasil Cek Screenshot

Berdasarkan hasil SQL Developer:

1. Query `DBA_TABLESPACES` untuk `VERIDITY_TS` menghasilkan 0 row.
   Artinya tablespace `VERIDITY_TS` belum dibuat, atau namanya berbeda.

2. Query `DBA_DATA_FILES` untuk `VERIDITY_TS` juga 0 row.
   Ini wajar karena tablespace belum ada.

3. Query `DBA_TS_QUOTAS` untuk `VERIDITY` juga 0 row.
   Artinya user `VERIDITY` belum diberi quota khusus pada tablespace `VERIDITY_TS`.

4. User `VERIDITY` memiliki role `DBA`, `RESOURCE`, dan `CONNECT`.
   Untuk laporan keamanan least privilege, role `DBA` terlalu tinggi. Lebih baik memakai privilege minimal.

5. Tabel sudah terpisah:
   - User `VERIDITY`: `USERS`, `FORENSIC_ANALYSES`.
   - User `DISTRI`: `USERS`, `PRODUCTS`, `ORDERS`, `ORDER_ITEMS`, `VOUCHERS`, `CATEGORIES`.

Struktur dua user ini sudah benar. Yang perlu direvisi adalah tablespace, quota, dan privilege.

## 3. Cek Lokasi Datafile Oracle

Sebelum membuat tablespace, cek lokasi datafile Oracle yang sedang dipakai.

Jalankan sebagai user DBA/SYS:

```sql
SELECT FILE_NAME, TABLESPACE_NAME
FROM DBA_DATA_FILES
ORDER BY TABLESPACE_NAME;
```

Gunakan folder yang sama untuk membuat datafile baru.

Contoh path Windows biasanya seperti:

```text
C:\APP\USER\PRODUCT\21C\ORADATA\XE\
```

Sesuaikan path pada sintaks di bawah dengan lokasi Oracle di laptop.

## 4. Buat Tablespace `VERIDITY_TS`

Jalankan sebagai `SYS AS SYSDBA` atau user DBA.

```sql
CREATE TABLESPACE VERIDITY_TS
DATAFILE 'C:\APP\USER\PRODUCT\21C\ORADATA\XE\veridity_ts01.dbf'
SIZE 500M
AUTOEXTEND ON
NEXT 100M
MAXSIZE 2G;
```

Jika path berbeda, ganti bagian:

```text
C:\APP\USER\PRODUCT\21C\ORADATA\XE\veridity_ts01.dbf
```

dengan path datafile Oracle yang benar.

## 5. Set Default Tablespace dan Quota untuk Dua User

Jalankan sebagai DBA/SYS:

```sql
ALTER USER VERIDITY DEFAULT TABLESPACE VERIDITY_TS;
ALTER USER DISTRI DEFAULT TABLESPACE VERIDITY_TS;

ALTER USER VERIDITY QUOTA UNLIMITED ON VERIDITY_TS;
ALTER USER DISTRI QUOTA UNLIMITED ON VERIDITY_TS;
```

Jika ingin quota dibatasi, gunakan contoh berikut:

```sql
ALTER USER VERIDITY QUOTA 500M ON VERIDITY_TS;
ALTER USER DISTRI QUOTA 500M ON VERIDITY_TS;
```

Untuk project akhir/demo, `UNLIMITED` masih aman selama hanya di database lokal.

## 6. Pindahkan Tabel Lama ke `VERIDITY_TS`

Jika tabel sudah terlanjur dibuat di tablespace lain seperti `USERS`, pindahkan tabelnya.

### 6.1 Pindahkan tabel schema `VERIDITY`

Jalankan sebagai DBA/SYS:

```sql
ALTER TABLE VERIDITY.USERS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.FORENSIC_ANALYSES MOVE TABLESPACE VERIDITY_TS;
```

### 6.2 Pindahkan tabel schema `DISTRI`

```sql
ALTER TABLE DISTRI.USERS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.CATEGORIES MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.PRODUCTS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.ORDERS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.ORDER_ITEMS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.VOUCHERS MOVE TABLESPACE VERIDITY_TS;
```

Jika ada tabel pendukung Laravel, pindahkan juga:

```sql
ALTER TABLE VERIDITY.CACHE MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.CACHE_LOCKS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.JOBS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.JOB_BATCHES MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.FAILED_JOBS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.PASSWORD_RESET_TOKENS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.SESSIONS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE VERIDITY.PERSONAL_ACCESS_TOKENS MOVE TABLESPACE VERIDITY_TS;

ALTER TABLE DISTRI.CACHE MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.CACHE_LOCKS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.JOBS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.JOB_BATCHES MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.FAILED_JOBS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.PASSWORD_RESET_TOKENS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.SESSIONS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.CART_ITEMS MOVE TABLESPACE VERIDITY_TS;
ALTER TABLE DISTRI.SHIPPING_ADDRESSES MOVE TABLESPACE VERIDITY_TS;
```

Catatan:

- Jika ada error `table or view does not exist`, berarti tabel tersebut memang belum ada di schema itu. Lewati saja.
- Setelah `ALTER TABLE ... MOVE`, index bisa menjadi unusable, jadi lakukan rebuild index.

## 7. Rebuild Index Setelah Move Tablespace

Jalankan sebagai DBA/SYS.

Penting:

- Jangan rebuild index bertipe `LOB` dengan `ALTER INDEX ... REBUILD TABLESPACE`.
- Index LOB dibuat dan dikelola otomatis oleh Oracle untuk kolom `CLOB`/`BLOB`.
- Jika index LOB ikut direbuild, Oracle dapat mengeluarkan error `ORA-02243: invalid ALTER INDEX or ALTER MATERIALIZED VIEW option`.
- Untuk LOB, pindahkan kolomnya dengan `ALTER TABLE ... MOVE LOB(...) STORE AS (TABLESPACE VERIDITY_TS)`.

### 7.1 Rebuild index schema `VERIDITY`

```sql
BEGIN
  FOR idx IN (
    SELECT OWNER, INDEX_NAME
    FROM DBA_INDEXES
    WHERE OWNER = 'VERIDITY'
      AND STATUS = 'UNUSABLE'
      AND INDEX_TYPE <> 'LOB'
  ) LOOP
    EXECUTE IMMEDIATE 'ALTER INDEX ' || idx.OWNER || '.' || idx.INDEX_NAME || ' REBUILD TABLESPACE VERIDITY_TS';
  END LOOP;
END;
/
```

Jika ingin rebuild semua index milik `VERIDITY`:

```sql
BEGIN
  FOR idx IN (
    SELECT OWNER, INDEX_NAME
    FROM DBA_INDEXES
    WHERE OWNER = 'VERIDITY'
      AND INDEX_TYPE <> 'LOB'
  ) LOOP
    EXECUTE IMMEDIATE 'ALTER INDEX ' || idx.OWNER || '.' || idx.INDEX_NAME || ' REBUILD TABLESPACE VERIDITY_TS';
  END LOOP;
END;
/
```

### 7.2 Rebuild index schema `DISTRI`

```sql
BEGIN
  FOR idx IN (
    SELECT OWNER, INDEX_NAME
    FROM DBA_INDEXES
    WHERE OWNER = 'DISTRI'
      AND INDEX_TYPE <> 'LOB'
  ) LOOP
    EXECUTE IMMEDIATE 'ALTER INDEX ' || idx.OWNER || '.' || idx.INDEX_NAME || ' REBUILD TABLESPACE VERIDITY_TS';
  END LOOP;
END;
/
```

### 7.3 Pindahkan kolom CLOB/BLOB schema `VERIDITY`

Jalankan ini untuk kolom besar milik `VERIDITY`.

```sql
ALTER TABLE VERIDITY.FORENSIC_ANALYSES MOVE LOB (METADATA_DETAILS) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FORENSIC_ANALYSES MOVE LOB (NOISE_STATUS) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FORENSIC_ANALYSES MOVE LOB (FINAL_RESULT) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FORENSIC_ANALYSES MOVE LOB (REPORT_ERROR) STORE AS (TABLESPACE VERIDITY_TS);

ALTER TABLE VERIDITY.SESSIONS MOVE LOB (PAYLOAD) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FAILED_JOBS MOVE LOB (CONNECTION) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FAILED_JOBS MOVE LOB (QUEUE) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FAILED_JOBS MOVE LOB (PAYLOAD) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.FAILED_JOBS MOVE LOB (EXCEPTION) STORE AS (TABLESPACE VERIDITY_TS);
```

Jika ada error `column does not exist`, berarti kolom tersebut tidak ada pada instalasi migrasi yang sedang dipakai. Lewati baris itu.

### 7.4 Pindahkan kolom CLOB/BLOB schema `DISTRI`

```sql
ALTER TABLE DISTRI.PRODUCTS MOVE LOB (DESCRIPTION) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.PRODUCTS MOVE LOB (IMAGE_URL) STORE AS (TABLESPACE VERIDITY_TS);

ALTER TABLE DISTRI.ORDERS MOVE LOB (PAYMENT_INSTRUCTION) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.ORDERS MOVE LOB (SHIPPING_ADDRESS) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.ORDERS MOVE LOB (VERIDITY_MESSAGE) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.ORDERS MOVE LOB (VERIDITY_VALIDATION_DETAILS) STORE AS (TABLESPACE VERIDITY_TS);

ALTER TABLE DISTRI.SHIPPING_ADDRESSES MOVE LOB (ADDRESS_LINE) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.SESSIONS MOVE LOB (PAYLOAD) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.FAILED_JOBS MOVE LOB (CONNECTION) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.FAILED_JOBS MOVE LOB (QUEUE) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.FAILED_JOBS MOVE LOB (PAYLOAD) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.FAILED_JOBS MOVE LOB (EXCEPTION) STORE AS (TABLESPACE VERIDITY_TS);
```

### 7.5 Cek index LOB yang dilewati

Gunakan query ini untuk melihat index LOB yang tidak perlu direbuild manual.

```sql
SELECT OWNER, INDEX_NAME, TABLE_NAME, INDEX_TYPE, TABLESPACE_NAME, STATUS
FROM DBA_INDEXES
WHERE OWNER IN ('VERIDITY', 'DISTRI')
  AND INDEX_TYPE = 'LOB'
ORDER BY OWNER, TABLE_NAME, INDEX_NAME;
```

Jika ingin memastikan LOB segment sudah berada di `VERIDITY_TS`:

```sql
SELECT OWNER, TABLE_NAME, COLUMN_NAME, SEGMENT_NAME, TABLESPACE_NAME
FROM DBA_LOBS
WHERE OWNER IN ('VERIDITY', 'DISTRI')
ORDER BY OWNER, TABLE_NAME, COLUMN_NAME;
```

## 8. Revisi Privilege agar Least Privilege

Saat ini dari screenshot user `VERIDITY` memiliki role `DBA`, `RESOURCE`, dan `CONNECT`.

Untuk laporan DBA, sebaiknya role `DBA` dicabut dari user aplikasi.

Sebelum revoke, cek role yang benar-benar sedang melekat:

```sql
SELECT GRANTEE, GRANTED_ROLE
FROM DBA_ROLE_PRIVS
WHERE GRANTEE IN ('VERIDITY', 'DISTRI')
ORDER BY GRANTEE, GRANTED_ROLE;
```

Jalankan sebagai DBA/SYS:

```sql
REVOKE DBA FROM VERIDITY;
REVOKE DBA FROM DISTRI;
```

Jika muncul error:

```text
ORA-01951: ROLE 'DBA' not granted to 'VERIDITY'
ORA-01951: ROLE 'DBA' not granted to 'DISTRI'
```

artinya role `DBA` memang sudah tidak melekat pada user tersebut. Kondisi ini aman dan bisa dilewati. Lanjutkan ke pengecekan role dengan query `DBA_ROLE_PRIVS`.

Jika query `DBA_ROLE_PRIVS` masih menampilkan role `DBA`, tetapi `REVOKE DBA FROM ...` tetap menghasilkan `ORA-01951`, kemungkinan besar worksheet SQL Developer sedang memakai container/koneksi berbeda, atau role `DBA` berasal dari common/inherited role.

Jalankan diagnosis ini pada worksheet yang sama dengan perintah `REVOKE`:

```sql
SHOW USER;

SELECT
    SYS_CONTEXT('USERENV', 'SESSION_USER') AS SESSION_USER,
    SYS_CONTEXT('USERENV', 'CURRENT_SCHEMA') AS CURRENT_SCHEMA,
    SYS_CONTEXT('USERENV', 'CON_NAME') AS CONTAINER_NAME,
    SYS_CONTEXT('USERENV', 'SERVICE_NAME') AS SERVICE_NAME
FROM DUAL;
```

Lalu cek detail role:

```sql
SELECT
    GRANTEE,
    GRANTED_ROLE,
    ADMIN_OPTION,
    DELEGATE_OPTION,
    DEFAULT_ROLE,
    COMMON,
    INHERITED
FROM DBA_ROLE_PRIVS
WHERE GRANTEE IN ('VERIDITY', 'DISTRI')
ORDER BY GRANTEE, GRANTED_ROLE;
```

Jika kolom `COMMON` atau `INHERITED` bernilai `YES`, revoke harus dilakukan dari container asal grant tersebut. Pada Oracle multitenant, biasanya perlu login sebagai `SYS AS SYSDBA` ke root/CDB lalu menjalankan revoke dengan container yang sesuai.

Contoh jika role diberikan secara common:

```sql
ALTER SESSION SET CONTAINER = CDB$ROOT;

REVOKE DBA FROM VERIDITY CONTAINER=ALL;
REVOKE DBA FROM DISTRI CONTAINER=ALL;
```

Berdasarkan hasil pengecekan:

```text
SESSION_USER   = SYSTEM
CURRENT_SCHEMA = SYSTEM
CONTAINER_NAME = CDB$ROOT
SERVICE_NAME   = SYS$USERS
COMMON         = YES
INHERITED      = NO
```

maka gunakan perintah berikut dari worksheet `SYSTEM` yang sedang berada di `CDB$ROOT`:

```sql
REVOKE DBA FROM VERIDITY CONTAINER=ALL;
REVOKE DBA FROM DISTRI CONTAINER=ALL;
```

Jika ingin sekalian merapikan agar tidak memakai role lama `CONNECT` dan `RESOURCE`, jalankan:

```sql
REVOKE CONNECT FROM VERIDITY CONTAINER=ALL;
REVOKE RESOURCE FROM VERIDITY CONTAINER=ALL;
REVOKE CONNECT FROM DISTRI CONTAINER=ALL;
REVOKE RESOURCE FROM DISTRI CONTAINER=ALL;
```

Lalu beri privilege eksplisit:

```sql
GRANT CREATE SESSION TO VERIDITY CONTAINER=ALL;
GRANT CREATE TABLE TO VERIDITY CONTAINER=ALL;
GRANT CREATE SEQUENCE TO VERIDITY CONTAINER=ALL;
GRANT CREATE VIEW TO VERIDITY CONTAINER=ALL;
GRANT CREATE PROCEDURE TO VERIDITY CONTAINER=ALL;
GRANT CREATE TRIGGER TO VERIDITY CONTAINER=ALL;

GRANT CREATE SESSION TO DISTRI CONTAINER=ALL;
GRANT CREATE TABLE TO DISTRI CONTAINER=ALL;
GRANT CREATE SEQUENCE TO DISTRI CONTAINER=ALL;
GRANT CREATE VIEW TO DISTRI CONTAINER=ALL;
GRANT CREATE PROCEDURE TO DISTRI CONTAINER=ALL;
GRANT CREATE TRIGGER TO DISTRI CONTAINER=ALL;
```

Setelah itu cek ulang:

```sql
SELECT
    GRANTEE,
    GRANTED_ROLE,
    COMMON,
    INHERITED
FROM DBA_ROLE_PRIVS
WHERE GRANTEE IN ('VERIDITY', 'DISTRI')
ORDER BY GRANTEE, GRANTED_ROLE;

SELECT
    GRANTEE,
    PRIVILEGE,
    COMMON,
    INHERITED
FROM DBA_SYS_PRIVS
WHERE GRANTEE IN ('VERIDITY', 'DISTRI')
ORDER BY GRANTEE, PRIVILEGE;
```

Target hasil:

- `DBA` tidak muncul di `DBA_ROLE_PRIVS`.
- Jika `CONNECT` dan `RESOURCE` juga dicabut, role tersebut juga tidak muncul.
- Privilege eksplisit seperti `CREATE SESSION`, `CREATE TABLE`, `CREATE SEQUENCE`, `CREATE VIEW`, `CREATE PROCEDURE`, dan `CREATE TRIGGER` muncul di `DBA_SYS_PRIVS`.

Jika user `VERIDITY` dan `DISTRI` adalah local user di PDB, masuk ke PDB/service yang sama dengan aplikasi, lalu revoke dari sana:

```sql
ALTER SESSION SET CONTAINER = XEPDB1;

REVOKE DBA FROM VERIDITY;
REVOKE DBA FROM DISTRI;
```

Ganti `XEPDB1` dengan nama PDB/service yang muncul dari query `SYS_CONTEXT('USERENV', 'CON_NAME')`.

Jika masih muncul `ORA-01951`, jalankan revoke aman berikut. Script ini hanya melakukan revoke jika role benar-benar terbaca pada container aktif:

```sql
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_count
    FROM DBA_ROLE_PRIVS
    WHERE GRANTEE = 'VERIDITY'
      AND GRANTED_ROLE = 'DBA';

    IF v_count > 0 THEN
        EXECUTE IMMEDIATE 'REVOKE DBA FROM VERIDITY';
    END IF;
END;
/

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_count
    FROM DBA_ROLE_PRIVS
    WHERE GRANTEE = 'DISTRI'
      AND GRANTED_ROLE = 'DBA';

    IF v_count > 0 THEN
        EXECUTE IMMEDIATE 'REVOKE DBA FROM DISTRI';
    END IF;
END;
/
```

Jika `RESOURCE` dan `CONNECT` ingin tetap dipakai untuk demo Laravel migration, itu masih bisa. Namun versi yang lebih rapi adalah mencabut role lama dan memberi privilege eksplisit.

```sql
REVOKE RESOURCE FROM VERIDITY;
REVOKE CONNECT FROM VERIDITY;
REVOKE RESOURCE FROM DISTRI;
REVOKE CONNECT FROM DISTRI;

GRANT CREATE SESSION TO VERIDITY;
GRANT CREATE TABLE TO VERIDITY;
GRANT CREATE SEQUENCE TO VERIDITY;
GRANT CREATE VIEW TO VERIDITY;
GRANT CREATE PROCEDURE TO VERIDITY;

GRANT CREATE SESSION TO DISTRI;
GRANT CREATE TABLE TO DISTRI;
GRANT CREATE SEQUENCE TO DISTRI;
GRANT CREATE VIEW TO DISTRI;
GRANT CREATE PROCEDURE TO DISTRI;
```

Jika setelah ini Laravel migration membutuhkan trigger, tambahkan:

```sql
GRANT CREATE TRIGGER TO VERIDITY;
GRANT CREATE TRIGGER TO DISTRI;
```

## 9. Query Verifikasi Setelah Revisi

### 9.1 Cek tablespace

```sql
SELECT TABLESPACE_NAME, STATUS, CONTENTS
FROM DBA_TABLESPACES
WHERE TABLESPACE_NAME = 'VERIDITY_TS';
```

Harus muncul 1 row.

### 9.2 Cek datafile

```sql
SELECT FILE_NAME, TABLESPACE_NAME, BYTES / 1024 / 1024 AS SIZE_MB, AUTOEXTENSIBLE
FROM DBA_DATA_FILES
WHERE TABLESPACE_NAME = 'VERIDITY_TS';
```

Harus muncul datafile `veridity_ts01.dbf`.

### 9.3 Cek quota

```sql
SELECT USERNAME, TABLESPACE_NAME, BYTES / 1024 / 1024 AS USED_MB, MAX_BYTES / 1024 / 1024 AS MAX_MB
FROM DBA_TS_QUOTAS
WHERE USERNAME IN ('VERIDITY', 'DISTRI')
ORDER BY USERNAME;
```

Harus muncul user `VERIDITY` dan `DISTRI`.

### 9.4 Cek role

```sql
SELECT * FROM DBA_ROLE_PRIVS
WHERE GRANTEE IN ('VERIDITY', 'DISTRI')
ORDER BY GRANTEE, GRANTED_ROLE;
```

Jika sudah menerapkan least privilege, role `DBA` tidak boleh muncul.

### 9.5 Cek privilege

```sql
SELECT * FROM DBA_SYS_PRIVS
WHERE GRANTEE IN ('VERIDITY', 'DISTRI')
ORDER BY GRANTEE, PRIVILEGE;
```

Minimal harus ada:

- `CREATE SESSION`
- `CREATE TABLE`
- `CREATE SEQUENCE`
- `CREATE VIEW`
- `CREATE PROCEDURE`

### 9.6 Cek tablespace tabel

```sql
SELECT OWNER, TABLE_NAME, TABLESPACE_NAME
FROM DBA_TABLES
WHERE OWNER IN ('VERIDITY', 'DISTRI')
ORDER BY OWNER, TABLE_NAME;
```

Targetnya tabel aplikasi berada di `VERIDITY_TS`.

### 9.7 Cek tablespace index

```sql
SELECT OWNER, INDEX_NAME, TABLE_NAME, TABLESPACE_NAME, STATUS
FROM DBA_INDEXES
WHERE OWNER IN ('VERIDITY', 'DISTRI')
ORDER BY OWNER, TABLE_NAME, INDEX_NAME;
```

Targetnya index berada di `VERIDITY_TS` dan status `VALID`.

## 10. Jika Ingin Membuat Ulang dari Nol

Jika database masih aman untuk dihapus dan ingin paling bersih, gunakan langkah ini.

Peringatan: sintaks ini menghapus user dan semua tabel di dalamnya.

```sql
DROP USER VERIDITY CASCADE;
DROP USER DISTRI CASCADE;
DROP TABLESPACE VERIDITY_TS INCLUDING CONTENTS AND DATAFILES;
```

Lalu buat ulang:

```sql
CREATE TABLESPACE VERIDITY_TS
DATAFILE 'C:\APP\USER\PRODUCT\21C\ORADATA\XE\veridity_ts01.dbf'
SIZE 500M
AUTOEXTEND ON
NEXT 100M
MAXSIZE 2G;

CREATE USER VERIDITY IDENTIFIED BY "password_oracle"
DEFAULT TABLESPACE VERIDITY_TS
TEMPORARY TABLESPACE TEMP
QUOTA UNLIMITED ON VERIDITY_TS;

CREATE USER DISTRI IDENTIFIED BY "password_oracle"
DEFAULT TABLESPACE VERIDITY_TS
TEMPORARY TABLESPACE TEMP
QUOTA UNLIMITED ON VERIDITY_TS;

GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE VIEW, CREATE PROCEDURE TO VERIDITY;
GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE VIEW, CREATE PROCEDURE TO DISTRI;
```

Setelah itu jalankan ulang migration Laravel:

```bash
cd veridity-laravel
php artisan config:clear
php artisan migrate:fresh --seed

cd ../distri
php artisan config:clear
php artisan migrate:fresh --seed
```

## 11. Revisi Narasi Laporan

Pada laporan, jelaskan bahwa implementasi Oracle memakai dua schema:

- Schema `VERIDITY` untuk aplikasi forensik utama.
- Schema `DISTRI` untuk aplikasi distributor/toko.

Keduanya berada dalam satu tablespace `VERIDITY_TS` agar administrasi storage tetap terpusat.

Kalimat yang bisa dipakai:

```text
Implementasi Oracle pada project VERIDITY menggunakan dua schema, yaitu VERIDITY dan DISTRI. Schema VERIDITY menyimpan data autentikasi dan hasil analisis forensik, sedangkan schema DISTRI menyimpan data toko, produk, order, voucher, dan validasi nota. Kedua schema ditempatkan pada tablespace VERIDITY_TS dengan quota terkontrol. Pemisahan schema dilakukan untuk menjaga batas kepemilikan data antar aplikasi, sedangkan penggunaan satu tablespace memudahkan administrasi storage.
```

## 12. Evaluasi Hasil Verifikasi Terbaru

Berdasarkan hasil pengecekan:

- `VERIDITY_TS` sudah ada, status `ONLINE`, contents `PERMANENT`.
- Datafile `VERIDITY_TS01.DBF` sudah ada dengan ukuran 500 MB dan `AUTOEXTENSIBLE = YES`.
- User `VERIDITY` dan `DISTRI` sudah memiliki quota pada `VERIDITY_TS`.
- `DBA_ROLE_PRIVS` untuk `VERIDITY` dan `DISTRI` sudah 0 row, artinya role `DBA`, `CONNECT`, dan `RESOURCE` sudah tidak melekat.
- `DBA_SYS_PRIVS` sudah menampilkan privilege eksplisit untuk kedua user:
  - `CREATE SESSION`
  - `CREATE TABLE`
  - `CREATE SEQUENCE`
  - `CREATE VIEW`
  - `CREATE PROCEDURE`
  - `CREATE TRIGGER`
- Mayoritas tabel sudah berada di `VERIDITY_TS`.
- Index berstatus `VALID`.

Catatan kecil yang masih terlihat:

1. Tabel `VERIDITY.MIGRATIONS` masih berada di tablespace `USERS`.
2. Beberapa index bernama `SYS_IL...` masih menampilkan tablespace `USERS`. Itu biasanya LOB index untuk kolom `CLOB/BLOB`, sehingga tidak dipindahkan dengan `ALTER INDEX ... REBUILD`.

### 12.1 Pindahkan tabel `MIGRATIONS`

Jika ingin semua tabel Laravel berada di `VERIDITY_TS`, jalankan:

```sql
ALTER TABLE VERIDITY.MIGRATIONS MOVE TABLESPACE VERIDITY_TS;
```

Lalu rebuild index non-LOB milik tabel tersebut:

```sql
BEGIN
  FOR idx IN (
    SELECT OWNER, INDEX_NAME
    FROM DBA_INDEXES
    WHERE OWNER = 'VERIDITY'
      AND TABLE_NAME = 'MIGRATIONS'
      AND INDEX_TYPE <> 'LOB'
  ) LOOP
    EXECUTE IMMEDIATE 'ALTER INDEX ' || idx.OWNER || '.' || idx.INDEX_NAME || ' REBUILD TABLESPACE VERIDITY_TS';
  END LOOP;
END;
/
```

### 12.2 Cek LOB segment yang masih berada di tablespace `USERS`

Gunakan query ini:

```sql
SELECT OWNER, TABLE_NAME, COLUMN_NAME, SEGMENT_NAME, INDEX_NAME, TABLESPACE_NAME
FROM DBA_LOBS
WHERE OWNER IN ('VERIDITY', 'DISTRI')
  AND TABLESPACE_NAME <> 'VERIDITY_TS'
ORDER BY OWNER, TABLE_NAME, COLUMN_NAME;
```

Jika hasilnya 0 row, maka LOB segment sudah bersih.

Jika masih ada row, pindahkan sesuai owner, table, dan column yang muncul. Contoh:

```sql
ALTER TABLE DISTRI.CACHE MOVE LOB (VALUE) STORE AS (TABLESPACE VERIDITY_TS);
```

Format umum:

```sql
ALTER TABLE OWNER.TABLE_NAME MOVE LOB (COLUMN_NAME) STORE AS (TABLESPACE VERIDITY_TS);
```

Catatan typo yang sering terjadi:

- Nama tabel Laravel yang benar adalah `JOB_BATCHES`, bukan `JOBS_BATCHES`.
- Jika menjalankan `ALTER TABLE DISTRI.JOBS_BATCHES ...`, Oracle akan mengeluarkan `ORA-00942` karena tabel tersebut memang tidak ada.

Untuk memindahkan LOB pada tabel `DISTRI.JOB_BATCHES`, cek dulu kolom LOB yang tersedia:

```sql
SELECT OWNER, TABLE_NAME, COLUMN_NAME, SEGMENT_NAME, INDEX_NAME, TABLESPACE_NAME
FROM DBA_LOBS
WHERE OWNER = 'DISTRI'
  AND TABLE_NAME = 'JOB_BATCHES'
ORDER BY COLUMN_NAME;
```

Lalu jalankan sesuai kolom yang muncul. Pada migration Laravel biasanya kolom LOB-nya adalah `FAILED_JOB_IDS` dan `OPTIONS`:

```sql
ALTER TABLE DISTRI.JOB_BATCHES MOVE LOB (FAILED_JOB_IDS) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE DISTRI.JOB_BATCHES MOVE LOB (OPTIONS) STORE AS (TABLESPACE VERIDITY_TS);
```

Jika `VERIDITY.JOB_BATCHES` juga masih memiliki LOB di tablespace `USERS`, jalankan:

```sql
ALTER TABLE VERIDITY.JOB_BATCHES MOVE LOB (FAILED_JOB_IDS) STORE AS (TABLESPACE VERIDITY_TS);
ALTER TABLE VERIDITY.JOB_BATCHES MOVE LOB (OPTIONS) STORE AS (TABLESPACE VERIDITY_TS);
```

Setelah LOB segment dipindahkan, cek ulang:

```sql
SELECT OWNER, INDEX_NAME, TABLE_NAME, TABLESPACE_NAME, STATUS
FROM DBA_INDEXES
WHERE OWNER IN ('VERIDITY', 'DISTRI')
  AND INDEX_NAME LIKE 'SYS_IL%'
ORDER BY OWNER, TABLE_NAME, INDEX_NAME;
```

Targetnya semua LOB index yang relevan ikut berada di `VERIDITY_TS` atau tidak ada lagi yang berada di `USERS`.

### 12.3 Kesimpulan Status

Untuk kebutuhan laporan, hasil verifikasi sudah memenuhi poin utama DBA:

- tablespace ada;
- datafile ada;
- quota user ada;
- role luas sudah dicabut;
- privilege eksplisit sudah diberikan;
- tabel dan index aplikasi valid.

Perbaikan `MIGRATIONS` dan LOB segment hanya bersifat perapian agar seluruh objek berada konsisten di `VERIDITY_TS`.
