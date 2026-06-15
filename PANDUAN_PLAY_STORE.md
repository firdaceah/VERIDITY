# PANDUAN DEPLOY VERIDITY MOBILE KE GOOGLE PLAY STORE

Panduan ini dibuat untuk menyiapkan `veridity_mobile` sebelum upload ke Google Play Store dalam format Android App Bundle (`.aab`).

Panduan ini juga sudah disesuaikan dengan modul dosen Pertemuan 13 tentang deployment Flutter:

```text
Testing aplikasi
  -> Menyiapkan release Android
  -> Mengubah version aplikasi
  -> Mengganti nama aplikasi
  -> Mengganti icon aplikasi
  -> Generate keystore
  -> Konfigurasi signing
  -> Build App Bundle (.aab)
  -> Upload ke Play Console
  -> Review Google
  -> Publish
```

Folder aplikasi:

```text
veridity_mobile
```

Output yang dibutuhkan Play Store:

```text
build/app/outputs/bundle/release/app-release.aab
```

Referensi resmi:

- Flutter Android release: https://docs.flutter.dev/deployment/android
- Android App Bundle: https://developer.android.com/guide/app-bundle
- Play App Signing: https://support.google.com/googleplay/android-developer/answer/9842756
- Create app di Play Console: https://support.google.com/googleplay/android-developer/answer/9859152

---

## 1. Perubahan yang Sudah Disiapkan

### 1.1 Bottom Navbar Lebih Aman untuk HP Android

File:

```text
veridity_mobile/lib/core/widgets/app_bottom_nav.dart
```

Masalah sebelumnya:

- Bottom navbar dibuat manual dengan `Positioned(bottom: 0)`.
- Tingginya tetap `80`.
- Pada beberapa HP Android, bagian bawah layar memiliki navigation bar bawaan seperti tombol `Back`, `Home`, dan `Recent Apps`.
- Jika tidak menghitung area aman bawah, navbar aplikasi bisa terlalu mepet atau tertutup.

Perbaikan:

```dart
final bottomInset = MediaQuery.viewPaddingOf(context).bottom;
final navHeight = 80.0 + bottomInset;
```

Artinya:

- Jika HP memakai gesture bar atau tombol navigasi bawah, aplikasi membaca tinggi area tersebut.
- Navbar VERIDITY otomatis menambah tinggi sesuai `viewPadding.bottom`.
- Konten halaman juga diberi padding bawah tambahan agar card terakhir tidak tertutup navbar.

Halaman yang sudah disesuaikan:

```text
home_page.dart
history_page.dart
help_page.dart
profile_page.dart
```

### 1.2 Nama Aplikasi Android

File:

```text
veridity_mobile/android/app/src/main/AndroidManifest.xml
```

Label aplikasi diubah menjadi:

```xml
android:label="VERIDITY"
```

Ini adalah nama yang tampil di perangkat Android.

### 1.3 Application ID Play Store

File:

```text
veridity_mobile/android/app/build.gradle.kts
```

Application ID diubah menjadi:

```kotlin
applicationId = "id.veridity.mobile"
```

Catatan penting:

- `applicationId` harus unik di Play Store.
- Jangan memakai `com.example...` untuk rilis.
- Jika kamu ingin nama package lain, ubah sebelum upload pertama. Setelah aplikasi pernah rilis di Play Store, package name tidak bisa diganti untuk aplikasi yang sama.

### 1.4 Signing Release

File:

```text
veridity_mobile/android/app/build.gradle.kts
```

Gradle sudah disiapkan untuk membaca:

```text
veridity_mobile/android/key.properties
```

File ini tidak boleh di-commit ke GitHub.

### 1.5 Backend Laravel dan Python Tetap Harus Online

VERIDITY mobile bukan aplikasi yang berdiri sendiri sepenuhnya. Aplikasi mobile mengambil data dari API Laravel.

Alur production:

```text
VERIDITY Mobile dari Play Store
        |
        v
API veridity-laravel yang sudah deploy
        |
        v
Python/FastAPI engine untuk analisis
        |
        v
Database + storage file/report
```

Artinya:

- Yang diupload ke Play Store adalah aplikasi Flutter dalam bentuk `.aab`.
- `veridity-laravel` tetap harus dideploy sebagai backend/API.
- `python` tetap harus berjalan di server sebagai engine analisis.
- Mobile diarahkan ke backend Laravel menggunakan `VERIDITY_API_BASE_URL`.
- Jika backend dimatikan, aplikasi mobile tetap bisa dibuka tetapi login, upload, history, dan download report tidak akan berjalan.

Untuk build Play Store, gunakan API production:

```bash
flutter build appbundle --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Jika dosen meminta "deploy jadi satu", maknanya adalah satu ekosistem:

```text
Play Store: veridity_mobile
Server: veridity-laravel + python + database
```

Bukan berarti Laravel dan Python dimasukkan ke dalam file `.aab`.

---

## 2. Checklist Responsivitas HP Sebelum Upload

Lakukan test pada minimal dua mode:

- HP dengan gesture navigation.
- HP dengan 3-button navigation: back, home, recent apps.

Jika hanya punya satu HP, test juga dengan emulator Android Studio.

### 2.1 Halaman yang Wajib Dicek

| Halaman | Yang Dicek |
| --- | --- |
| Splash | Tidak ada error layout |
| Login | Keyboard tidak menutup input email/password |
| Register | Semua input dapat discroll |
| Home | Navbar tidak tertutup tombol bawaan HP |
| Upload | Tombol `Unggah & Analisis` terlihat dan tidak overflow |
| Loading Analisis | Teks loading tidak kepotong |
| History | Card terakhir tidak tertutup navbar |
| Detail Analisis | Tombol `Download PDF` terlihat |
| Help | FAQ terakhir tidak tertutup navbar |
| Profile | Tombol Edit/Logout tidak tertutup navbar |

### 2.2 Cara Test Langsung di HP

Hubungkan HP ke laptop.

```bash
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity_mobile
flutter devices
```

Jalankan aplikasi:

```bash
flutter run --dart-define=VERIDITY_API_BASE_URL=https://DOMAIN-VERIDITY/api
```

Jika masih memakai IP EC2 tanpa HTTPS:

```bash
flutter run --dart-define=VERIDITY_API_BASE_URL=http://PUBLIC_IP/api
```

Catatan:

- Untuk Play Store, sebaiknya gunakan HTTPS/domain.
- Jika masih HTTP, aplikasi masih mengizinkan cleartext karena `android:usesCleartextTraffic="true"`, tetapi ini kurang ideal untuk production.

### 2.3 Cara Mengaktifkan 3-Button Navigation di HP

Di banyak HP Android:

```text
Settings
  -> System
  -> Gestures
  -> System navigation
  -> 3-button navigation
```

Nama menu bisa berbeda:

- Navigation bar
- System navigation
- Gestures
- Tombol navigasi

Setelah aktif, buka aplikasi dan cek apakah navbar VERIDITY tidak tertutup tombol bawaan Android.

### 2.4 Cara Menguji Ukuran Layar Kecil

Jika memakai emulator:

1. Buka Android Studio.
2. Buka `Device Manager`.
3. Buat emulator kecil, misalnya Pixel 3a atau layar sekitar 5-6 inch.
4. Jalankan:

```bash
flutter run
```

Yang perlu dicek:

- Tidak ada garis kuning-hitam overflow.
- Tombol bawah masih bisa diklik.
- Card terakhir bisa discroll sampai terlihat.
- Dialog lupa password masih bisa discroll.

---

## 3. Persiapan API Sebelum Rilis

File:

```text
veridity_mobile/lib/core/config/api_config.dart
```

Saat ini base URL dapat diganti ketika build:

```dart
static const String baseUrl = String.fromEnvironment(
  'VERIDITY_API_BASE_URL',
  defaultValue: 'http://54.169.169.253/api',
);
```

Untuk rilis Play Store, gunakan:

```bash
--dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Rekomendasi:

- Pakai domain dan HTTPS.
- Jangan bergantung pada public IP EC2 yang bisa berubah.
- Pastikan backend Laravel sudah production.
- Pastikan upload file dan download PDF dapat diakses dari internet.

### 3.1 Checklist Backend yang Harus Hidup

Sebelum upload ke Play Store, pastikan server backend ini berjalan:

| Komponen | Wajib Online? | Fungsi |
| --- | --- | --- |
| `veridity-laravel` | Ya | API login, upload, riwayat, detail, report |
| `python/main_api.py` | Ya | Analisis dokumen dan generate report dokumen |
| `python/analyze_all.py` | Ya, lewat Laravel | Analisis foto ELA, noise, metadata, deepfake |
| Database | Ya | Menyimpan user dan riwayat analisis |
| Storage file/report | Ya | Menampilkan file upload dan PDF report |

Test endpoint dari browser/laptop:

```text
https://domain-veridity.com/api/login
```

Endpoint `GET` mungkin menampilkan `405 Method Not Allowed`, itu masih wajar karena login memakai `POST`. Yang penting domain API merespons dari internet.

Test dari HP:

```bash
flutter run --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Kemudian coba:

- login;
- upload foto kecil;
- upload PDF kecil;
- buka riwayat;
- download PDF.

---

## 4. Naikkan Version Code dan Version Name

File:

```text
veridity_mobile/pubspec.yaml
```

Contoh:

```yaml
version: 1.0.0+1
```

Makna:

- `1.0.0` = version name yang dilihat user.
- `+1` = version code untuk Play Store.

Untuk update berikutnya:

```yaml
version: 1.0.1+2
```

Aturan penting:

- Setiap upload AAB baru harus punya `versionCode` lebih tinggi.
- Jika Play Store menolak karena version code sama, naikkan angka setelah `+`.

---

## 5. Mengubah Identitas Aplikasi

Identitas aplikasi perlu disiapkan sebelum upload pertama ke Play Store.

### 5.1 Nama Aplikasi yang Tampil di HP

File:

```text
veridity_mobile/android/app/src/main/AndroidManifest.xml
```

Bagian:

```xml
<application
    android:label="VERIDITY"
    android:icon="@mipmap/ic_launcher">
```

`android:label` adalah nama aplikasi yang tampil di HP.

Untuk VERIDITY, gunakan:

```xml
android:label="VERIDITY"
```

### 5.2 Package Name / Application ID

File:

```text
veridity_mobile/android/app/build.gradle.kts
```

Bagian:

```kotlin
defaultConfig {
    applicationId = "id.veridity.mobile"
}
```

Catatan:

- `applicationId` adalah identitas unik aplikasi di Play Store.
- Jangan gunakan `com.example...`.
- Tentukan sebelum upload pertama.
- Setelah aplikasi terdaftar di Play Store, `applicationId` tidak bisa diganti untuk aplikasi yang sama.

Jika dosen atau tim ingin nama lain, contoh:

```kotlin
applicationId = "id.ac.pens.veridity"
```

Pilih salah satu, lalu jangan diganti lagi setelah rilis.

### 5.3 Nama Aplikasi di Play Store

Di Play Console, app name dapat diisi:

```text
VERIDITY
```

Nama Play Store dapat berbeda dari `applicationId`. Yang dilihat user adalah nama aplikasi, sedangkan yang dibaca sistem Android/Google adalah `applicationId`.

---

## 6. Mengubah Icon Aplikasi Menjadi Logo VERIDITY

Masalah saat ini:

- Manifest sudah memakai:

```xml
android:icon="@mipmap/ic_launcher"
```

- Tetapi file `ic_launcher.png` di folder Android masih icon default Flutter.
- Jadi setelah aplikasi diinstall, logo yang muncul bisa masih default.

Solusi paling mudah mengikuti modul dosen: gunakan package `flutter_launcher_icons`.

### 6.1 Siapkan File Logo

Saat ini project punya logo:

```text
veridity_mobile/assets/images/logo.png
```

Untuk launcher icon, sebaiknya siapkan file icon khusus:

```text
veridity_mobile/assets/icon/veridity_icon.png
```

Spesifikasi yang disarankan:

- Format: PNG
- Ukuran: `1024 x 1024 px`
- Bentuk: square/persegi
- Background tidak terlalu transparan
- Logo VERIDITY berada di tengah
- Jangan ada teks kecil yang sulit dibaca

Jika pakai Canva:

1. Buat desain ukuran `1024 x 1024 px`.
2. Masukkan logo VERIDITY.
3. Pastikan background rapi.
4. Export sebagai PNG.
5. Simpan ke:

```text
veridity_mobile/assets/icon/veridity_icon.png
```

Jika folder `assets/icon` belum ada, buat foldernya.

### 6.2 Install Package

Masuk folder Flutter:

```bash
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity_mobile
```

Jalankan:

```bash
flutter pub add dev:flutter_launcher_icons
```

Alternatif sesuai modul dosen:

```bash
flutter pub add flutter_launcher_icons
```

Rekomendasi: pakai sebagai `dev_dependencies`, karena package ini hanya dipakai untuk generate icon, bukan saat aplikasi berjalan.

### 6.3 Tambahkan Config di `pubspec.yaml`

File:

```text
veridity_mobile/pubspec.yaml
```

Tambahkan di level paling kiri, sejajar dengan `flutter:`.

```yaml
flutter_launcher_icons:
  android: true
  ios: false
  image_path: "assets/icon/veridity_icon.png"
  adaptive_icon_background: "#111028"
  adaptive_icon_foreground: "assets/icon/veridity_icon.png"
```

Jika ingin memakai logo yang sudah ada tanpa membuat file baru:

```yaml
flutter_launcher_icons:
  android: true
  ios: false
  image_path: "assets/images/logo.png"
  adaptive_icon_background: "#111028"
  adaptive_icon_foreground: "assets/images/logo.png"
```

Namun lebih disarankan membuat `assets/icon/veridity_icon.png` yang square agar hasil launcher icon tidak kepotong.

### 6.4 Generate Icon

Jalankan:

```bash
dart run flutter_launcher_icons
```

Jika command di atas gagal, coba:

```bash
flutter pub run flutter_launcher_icons
```

Setelah berhasil, package akan mengganti file:

```text
android/app/src/main/res/mipmap-mdpi/ic_launcher.png
android/app/src/main/res/mipmap-hdpi/ic_launcher.png
android/app/src/main/res/mipmap-xhdpi/ic_launcher.png
android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png
android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png
```

### 6.5 Cek Logo Setelah Install

Jalankan:

```bash
flutter clean
flutter pub get
flutter run --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Cek di HP:

- Icon aplikasi di homescreen sudah logo VERIDITY.
- Nama aplikasi sudah `VERIDITY`.
- Icon tidak gepeng, tidak kepotong, dan tidak masih default Flutter.

Jika icon belum berubah:

1. Uninstall aplikasi dari HP.
2. Jalankan ulang:

```bash
flutter clean
flutter pub get
dart run flutter_launcher_icons
flutter run --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

3. Install ulang.

Kadang Android masih menyimpan cache icon lama.

---

## 7. Membuat Upload Key

Jalankan di PowerShell.

Contoh lokasi aman:

```powershell
mkdir C:\Users\user\keys
```

Buat keystore:

```powershell
keytool -genkey -v -keystore C:\Users\user\keys\veridity-upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Isi data yang diminta:

- Password keystore
- Nama
- Organisasi
- Kota
- Provinsi
- Kode negara, misalnya `ID`

Catatan penting:

- Simpan file `.jks` dengan aman.
- Jangan upload ke GitHub.
- Jangan hilangkan password.
- Jika upload key hilang, proses reset di Play Console bisa memakan waktu.

---

## 8. Membuat `key.properties`

Mengikuti modul dosen, ada dua pilihan lokasi keystore.

### Pilihan A - Keystore di Folder Aman Laptop

Ini lebih aman karena file `.jks` tidak berada di dalam project.

Buat file:

```text
veridity_mobile/android/key.properties
```

Isi:

```properties
storePassword=PASSWORD_KEYSTORE
keyPassword=PASSWORD_KEY
keyAlias=upload
storeFile=C:\\Users\\user\\keys\\veridity-upload-keystore.jks
```

Catatan Windows:

- Gunakan double backslash `\\`.
- File `key.properties` sudah masuk `.gitignore`, jadi tidak perlu di-commit.

### Pilihan B - Mengikuti Modul Dosen, Keystore di `android/app`

Jika ingin mengikuti persis modul dosen:

1. Pindahkan file:

```text
upload-keystore.jks
```

ke:

```text
veridity_mobile/android/app/upload-keystore.jks
```

2. Isi `veridity_mobile/android/key.properties`:

```properties
storePassword=PASSWORD_KEYSTORE
keyPassword=PASSWORD_KEY
keyAlias=upload
storeFile=../app/upload-keystore.jks
```

Catatan:

- File `.jks` tetap jangan diupload ke GitHub.
- `.gitignore` Android sudah mengabaikan `**/*.jks`.
- Simpan backup `.jks` di flashdisk/cloud pribadi yang aman.

---

## 9. Konfigurasi Signing di Gradle

Pada modul dosen, file yang dicontohkan biasanya:

```text
android/app/build.gradle
```

Pada project VERIDITY, file yang dipakai adalah Kotlin DSL:

```text
android/app/build.gradle.kts
```

Konfigurasinya sudah disiapkan:

```kotlin
val keystoreProperties = Properties()
val keystorePropertiesFile = rootProject.file("key.properties")

if (hasReleaseKeystore) {
    keystoreProperties.load(FileInputStream(keystorePropertiesFile))
}
```

Signing release:

```kotlin
signingConfigs {
    create("release") {
        if (hasReleaseKeystore) {
            keyAlias = keystoreProperties["keyAlias"] as String
            keyPassword = keystoreProperties["keyPassword"] as String
            storeFile = keystoreProperties["storeFile"]?.let { file(it) }
            storePassword = keystoreProperties["storePassword"] as String
        }
    }
}
```

Build release:

```kotlin
buildTypes {
    release {
        signingConfig = if (hasReleaseKeystore) {
            signingConfigs.getByName("release")
        } else {
            signingConfigs.getByName("debug")
        }
    }
}
```

Catatan:

- Kalau `key.properties` ada, AAB akan ditandatangani upload key.
- Kalau `key.properties` tidak ada, Gradle fallback ke debug signing. Ini hanya untuk test lokal, jangan upload ke Play Store.

---

## 10. Perintah Cek Sebelum Build

Masuk folder Flutter:

```bash
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity_mobile
```

Bersihkan build:

```bash
flutter clean
```

Ambil dependency:

```bash
flutter pub get
```

Analisis kode:

```bash
flutter analyze
```

Jalankan test jika ada:

```bash
flutter test
```

Test release di HP:

```bash
flutter run --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Jika semua aman, lanjut build AAB.

---

## 11. Build APK dan AAB

### 11.1 Build APK untuk Testing Manual

APK dipakai untuk install manual/testing, bukan upload utama Play Store.

```bash
flutter build apk --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Output:

```text
build/app/outputs/flutter-apk/app-release.apk
```

### 11.2 Build AAB untuk Play Store

Build untuk Play Store:

```bash
flutter build appbundle --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Jika masih memakai IP:

```bash
flutter build appbundle --release --dart-define=VERIDITY_API_BASE_URL=http://PUBLIC_IP/api
```

Output:

```text
veridity_mobile/build/app/outputs/bundle/release/app-release.aab
```

Pastikan file `.aab` ada:

```powershell
dir build\app\outputs\bundle\release
```

---

## 12. Upload ke Play Console

### 12.1 Buat Aplikasi

1. Buka Google Play Console.
2. Klik `Create app`.
3. Isi:
   - App name: `VERIDITY`
   - Default language: Indonesia atau English
   - App or game: App
   - Free or paid: Free
   - Contact email
4. Centang deklarasi yang diminta.
5. Klik `Create app`.

### 12.2 Isi Informasi Store Listing

Siapkan:

- Short description
- Full description
- App icon 512x512
- Feature graphic 1024x500
- Screenshot phone minimal 2 gambar
- Kategori aplikasi
- Email kontak
- Privacy policy URL

Contoh short description:

```text
VERIDITY membantu menganalisis keaslian foto dan dokumen menggunakan metode forensik digital dan AI detection.
```

Contoh full description:

```text
VERIDITY adalah aplikasi forensik digital untuk membantu pengguna menganalisis indikasi keaslian foto dan dokumen. Aplikasi ini terhubung dengan sistem backend VERIDITY untuk menjalankan analisis seperti Error Level Analysis, noise analysis, metadata analysis, AI/deepfake detection, dan NLP document detection. Hasil analisis ditampilkan dalam bentuk ringkasan, detail forensik, riwayat, serta laporan PDF.
```

### 12.3 Isi App Content

Bagian yang biasanya diminta:

- Privacy Policy
- Data Safety
- Ads: pilih tidak jika aplikasi tidak memakai iklan
- App access: jelaskan jika aplikasi perlu login
- Target audience
- Content rating questionnaire
- News apps declaration jika muncul
- Government apps declaration jika muncul

Untuk `App access`, tulis akun demo jika reviewer perlu login:

```text
Email: demo@example.com
Password: passworddemo
```

Gunakan akun demo khusus, bukan akun pribadi.

### 12.4 Upload AAB

Untuk uji awal:

1. Masuk ke `Testing`.
2. Pilih `Internal testing`.
3. Klik `Create new release`.
4. Upload:

```text
app-release.aab
```

5. Isi release notes.
6. Save.
7. Review release.
8. Start rollout to internal testing.

Jika internal testing aman, lanjut ke:

- Closed testing
- Open testing
- Production

---

## 13. Checklist Sebelum Submit Review

- [ ] Aplikasi bisa login.
- [ ] Upload foto berhasil.
- [ ] Upload PDF berhasil.
- [ ] Riwayat tampil.
- [ ] Detail analisis tampil.
- [ ] Download PDF berjalan.
- [ ] Navbar tidak tertutup navigation bar Android.
- [ ] Tidak ada overflow kuning-hitam.
- [ ] API production bisa diakses dari jaringan luar.
- [ ] App icon sudah diganti dari default Flutter.
- [ ] Nama aplikasi sudah `VERIDITY`.
- [ ] Application ID bukan `com.example`.
- [ ] Backend `veridity-laravel` sudah online.
- [ ] Python engine di server sudah online.
- [ ] `VERIDITY_API_BASE_URL` mengarah ke API production.
- [ ] Version code lebih tinggi dari upload sebelumnya.
- [ ] AAB ditandatangani upload key.
- [ ] Privacy policy tersedia.
- [ ] Akun demo reviewer tersedia jika aplikasi wajib login.

---

## 14. Catatan Penting untuk Review Google Play

### 14.1 Aplikasi Wajib Login

Jika semua fitur butuh login, Google reviewer perlu akun demo. Isi di:

```text
App content -> App access
```

### 14.2 Data Safety

Karena VERIDITY menerima upload foto/dokumen dan login user, bagian Data Safety harus jujur menyebutkan data yang dikumpulkan/diproses, misalnya:

- Email
- Nama
- Foto/dokumen yang diunggah
- File report
- Riwayat analisis

### 14.3 Privacy Policy

Play Store biasanya meminta privacy policy untuk aplikasi yang:

- login user;
- upload file;
- memproses data user;
- mengakses internet.

Minimal privacy policy menjelaskan:

- data apa yang dikumpulkan;
- untuk apa data diproses;
- apakah data dibagikan;
- cara user meminta penghapusan data;
- kontak developer.

### 14.4 HTTPS

Sebaiknya backend memakai HTTPS. Jika masih memakai HTTP/IP publik:

- risiko keamanan lebih tinggi;
- beberapa jaringan dapat memblokir;
- review produksi lebih aman jika sudah HTTPS.

### 14.5 Aplikasi Menggunakan Backend

Karena VERIDITY mobile membutuhkan API Laravel, pastikan backend tidak dimatikan selama proses review Google.

Yang harus aktif:

```text
https://domain-veridity.com/api
```

Jika reviewer Google membuka aplikasi tetapi backend mati:

- login gagal;
- upload gagal;
- history tidak muncul;
- aplikasi bisa dianggap tidak berfungsi.

Sediakan akun demo di Play Console:

```text
Email: demo@example.com
Password: passworddemo
```

Pastikan akun demo tersebut benar-benar bisa login ke backend production.

---

## 15. Jika Build Gagal

### Error: Keystore Tidak Ditemukan

Cek:

```text
veridity_mobile/android/key.properties
```

Pastikan `storeFile` benar:

```properties
storeFile=C:\\Users\\user\\keys\\veridity-upload-keystore.jks
```

### Error: Version Code Sudah Digunakan

Naikkan versi di `pubspec.yaml`:

```yaml
version: 1.0.1+2
```

Lalu build ulang.

### Error: Server Tidak Merespon

Cek base URL:

```bash
--dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Cek juga:

- backend Laravel hidup;
- domain mengarah ke server;
- HTTPS aktif;
- endpoint `/api/login` bisa diakses.

### Error: Icon Masih Default Flutter

Solusi:

```bash
flutter clean
flutter pub get
dart run flutter_launcher_icons
flutter run --release
```

Jika di HP masih sama:

1. Uninstall aplikasi.
2. Restart HP jika perlu.
3. Install ulang.

Android kadang menyimpan cache icon launcher.

### Error: Upload Gagal untuk File Besar

Cek limit backend:

- Nginx `client_max_body_size`
- PHP `upload_max_filesize`
- PHP `post_max_size`
- Laravel validation `max`

---

## 16. Ringkasan Sesuai Modul Dosen

| Modul Dosen | Penerapan di VERIDITY |
| --- | --- |
| Mengubah version | `pubspec.yaml`, contoh `version: 1.0.0+1` |
| Mengganti nama aplikasi | `AndroidManifest.xml`, `android:label="VERIDITY"` |
| Mengubah icon aplikasi | `flutter_launcher_icons`, logo dari `assets/icon/veridity_icon.png` |
| Membuat keystore | `keytool -genkey ... -alias upload` |
| Menyimpan keystore | Folder aman atau `android/app/upload-keystore.jks` |
| Membuat `key.properties` | `veridity_mobile/android/key.properties` |
| Konfigurasi signing | `android/app/build.gradle.kts` |
| Build APK | `flutter build apk --release` |
| Build AAB | `flutter build appbundle --release` |
| Upload Play Console | Upload `app-release.aab` |
| Store Listing | Icon, screenshot, deskripsi, feature graphic |
| App Content | Privacy policy, data safety, app access, rating |
| Publish | `Send for review` |

---

## 17. Catatan Verifikasi di Workspace Ini

Yang sudah dicek/diperbaiki:

- Bottom navbar membaca `MediaQuery.viewPadding.bottom`.
- Padding bawah halaman ber-navbar disesuaikan.
- Nama aplikasi Android diubah menjadi `VERIDITY`.
- `applicationId` Android diubah menjadi `id.veridity.mobile`.
- Gradle release signing disiapkan untuk membaca `android/key.properties`.

Yang perlu kamu jalankan langsung di laptop sebelum upload:

```bash
flutter analyze
flutter test
flutter run --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
flutter build appbundle --release --dart-define=VERIDITY_API_BASE_URL=https://domain-veridity.com/api
```

Catatan:

Pada pemeriksaan ini, `flutter analyze` dan `dart format` sempat timeout di environment Codex, jadi hasil final build `.aab` perlu kamu verifikasi langsung dari terminal lokal.
