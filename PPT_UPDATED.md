# PPT VERIDITY - PRAKTEK KECERDASAN BUATAN

Dokumen ini adalah naskah isi presentasi untuk mata kuliah Praktek Kecerdasan Buatan. Fokus presentasi adalah `veridity-laravel` dan `python`, terutama metode analisis, rumus, threshold, bobot, alur keputusan, serta bagaimana sistem menghasilkan hasil akhir.

Format yang disarankan:

- Gunakan satu bagian `Slide` sebagai satu halaman PowerPoint/Canva.
- Masukkan rumus sebagai elemen utama, jangan terlalu kecil.
- Tampilkan contoh foto asli, ELA map, noise map, dan PDF berarsir saat demo.
- Gunakan istilah "indikasi forensik" dan hindari klaim bahwa sistem memberi vonis hukum mutlak.

Pemilihan slide berdasarkan durasi:

- Presentasi `10-15 menit`: gunakan Slide 1-5, 6-8, 9-11, 14-20, 21-26, 31-35, dan 38.
- Presentasi `20-30 menit`: gunakan seluruh slide utama.
- Slide 36-37 dipakai sebagai latihan narasi demo.
- Lampiran A-C dipakai untuk menjawab pertanyaan dosen.

---

## Slide 1 - Judul

### VERIDITY

**Sistem Analisis Forensik Foto dan Dokumen untuk Mendeteksi Indikasi Manipulasi dan Konten Buatan AI**

Mata Kuliah: Praktek Kecerdasan Buatan

Teknologi utama:

- Laravel
- Python
- FastAPI
- Computer Vision
- Natural Language Processing

**Visual yang disarankan:** Logo VERIDITY, contoh foto, ELA map, dan dokumen dengan highlight.

**Catatan pembicara:**

> VERIDITY adalah sistem forensik digital yang membantu menganalisis apakah sebuah foto memiliki indikasi editan, deepfake, atau hasil AI, serta apakah sebuah dokumen didominasi tulisan manusia, AI, atau campuran keduanya.

---

## Slide 2 - Latar Belakang

### Permasalahan

- Foto digital mudah diedit, ditempel, dihapus objeknya, atau dibuat menggunakan AI.
- Dokumen dapat dibuat sepenuhnya oleh AI atau disusun dengan bantuan AI.
- Pemeriksaan secara visual oleh manusia tidak selalu cukup.
- Dibutuhkan sistem yang menggabungkan beberapa indikator agar hasil lebih mudah dipertanggungjawabkan.

### Solusi VERIDITY

VERIDITY menggunakan beberapa metode analisis secara bersamaan:

1. Error Level Analysis (ELA)
2. Noise Analysis
3. Metadata Analysis
4. Deepfake / GAN Fingerprint Detection
5. NLP AI Text Detection
6. OCR Tesseract untuk validasi teks pada gambar nota

**Catatan pembicara:**

> Satu metode tidak cukup untuk menentukan keaslian file. Karena itu VERIDITY memakai pendekatan multi-metode. Hasil akhir tidak hanya bergantung pada satu skor, tetapi pada gabungan beberapa bukti digital.

---

## Slide 3 - Tujuan Sistem

### Tujuan Utama

- Memberikan indikasi awal keaslian foto.
- Menemukan pola kompresi atau noise yang tidak konsisten.
- Mendeteksi pola spektral yang menyerupai hasil generator AI.
- Mengukur kontribusi tulisan manusia pada dokumen.
- Menghasilkan laporan PDF yang dapat dibaca user dan peneliti.

### Hasil yang Diberikan

| Jenis File | Hasil Utama |
| --- | --- |
| Foto | Asli, Mencurigakan, Sangat Berbahaya, atau Deepfake AI |
| Dokumen | Human Written, Mixed AI Assisted, atau Mayoritas AI Generated |

---

## Slide 4 - Arsitektur Sistem

### Pembagian Tanggung Jawab

```text
User Website / Mobile
        |
        v
veridity-laravel
Validasi, autentikasi, penyimpanan, API, riwayat
        |
        v
python
Analisis foto, NLP dokumen, dan pembuatan PDF berarsir
        |
        v
Database + PDF Report
        |
        v
Hasil ditampilkan kepada user
```

### Peran Laravel

- Menerima upload file.
- Memvalidasi tipe dan ukuran file.
- Memanggil Python.
- Menyimpan hasil ke database.
- Menampilkan hasil dan laporan.

### Peran Python

- Menjalankan komputasi forensik.
- Menghasilkan skor setiap metode.
- Menghasilkan JSON hasil analisis.
- Membuat visual ELA, noise map, dan PDF berarsir.

**Catatan pembicara:**

> Laravel bertindak sebagai pusat aplikasi, sedangkan Python menjadi engine komputasi. Pemisahan ini dilakukan karena library pengolahan citra, NLP, dan model AI lebih banyak tersedia di Python.

---

## Slide 5 - Alur Analisis Foto

```text
Upload Foto
   |
   v
Laravel menyimpan file
   |
   v
Laravel menjalankan analyze_all.py
   |
   +--> Metadata Analysis
   +--> Error Level Analysis
   +--> Deepfake / GAN Detection
   +--> Noise Analysis
   |
   v
Menghitung skor keaslian setiap metode
   |
   v
Menghitung final_score berbobot
   |
   v
Python menentukan verdict dasar
   |
   v
Laravel menerjemahkan hasil menjadi label user
   |
   v
Simpan database + generate PDF report
```

File utama:

- `python/analyze_all.py`
- `veridity-laravel/app/Http/Controllers/Api/ForensicController.php`

---

## Slide 6 - Metode 1: Error Level Analysis

### Konsep ELA

ELA mendeteksi perbedaan jejak kompresi pada gambar.

- Gambar JPEG asli biasanya memiliki tingkat kompresi yang relatif konsisten.
- Area yang ditempel atau diedit dapat memiliki error kompresi berbeda.
- Area dengan perbedaan tinggi dapat terlihat lebih terang pada ELA map.

### Alur ELA

```text
Gambar asli
   |
   v
Kompresi ulang menjadi JPEG
   |
   v
Hitung selisih piksel
   |
   v
Hitung rata-rata dan standar deviasi
   |
   v
ELA anomaly score
   |
   v
ELA authenticity score
```

File: `python/analysis/ela.py`

---

## Slide 7 - Rumus ELA

### Selisih Piksel

```text
D(x, y) = |I_original(x, y) - I_compressed(x, y)|
```

Keterangan:

- `I_original` adalah piksel gambar sebelum dikompresi ulang.
- `I_compressed` adalah piksel gambar setelah dikompresi ulang.
- `D` adalah nilai error kompresi setiap piksel.

### Skor Anomali ELA

```text
ELA_anomaly = mean(D) + 2 * std(D)
```

### Konversi Menjadi Skor Keaslian

```text
A_ela = max(0, min(100, 100 - (ELA_anomaly * 3)))
```

Interpretasi:

- `ELA_anomaly` kecil berarti kompresi lebih homogen.
- `ELA_anomaly` besar berarti ada area dengan perbedaan kompresi tinggi.
- `A_ela` tinggi berarti gambar lebih konsisten menurut ELA.

---

## Slide 8 - Threshold ELA

### Threshold yang Digunakan

| Kondisi | Interpretasi |
| --- | --- |
| `ELA_anomaly <= 5` | Sangat rendah, mendukung foto asli |
| `ELA_anomaly > 8` dan noise tidak konsisten | Mulai perlu dicurigai |
| `ELA_anomaly > 15` | Mencurigakan |
| `ELA_anomaly > 30` | Python menganggap manipulasi |
| `ELA_anomaly > 45` | Laravel memberi status sangat berbahaya |

### Threshold Mask Visual

```text
mask_pixel = 255 jika ELA_pixel > 40
mask_pixel = 0   jika ELA_pixel <= 40
```

Threshold `40` dipakai untuk membuat mask visual area ELA yang menonjol.

**Catatan penting:**

Threshold ELA adalah nilai heuristik/empiris pada implementasi VERIDITY. Nilai ini perlu diuji lagi menggunakan dataset foto asli dan manipulasi yang lebih besar agar semakin kuat secara ilmiah.

---

## Slide 9 - Metode 2: Noise Analysis

### Konsep Noise

Noise adalah residu atau variasi kecil pada piksel yang muncul dari sensor kamera, kompresi, dan proses pengolahan gambar.

- Foto kamera biasanya memiliki pola noise alami.
- Area editan, smoothing, blur, atau tempelan dapat memiliki noise berbeda.
- VERIDITY membandingkan sebaran noise antar area gambar.

### Alur Noise Analysis

```text
Pisahkan channel RGB
   |
   v
Gaussian Blur pada setiap channel
   |
   v
Kurangkan gambar asli dengan hasil blur
   |
   v
Dapatkan high-pass noise
   |
   v
Bagi gambar menjadi blok 64x64
   |
   v
Hitung varians noise tiap blok
   |
   v
Nilai konsistensi noise
```

File: `python/analysis/noise_map.py`

---

## Slide 10 - Rumus Noise Analysis

### Ekstraksi Noise

```text
N_channel = |Channel_original - GaussianBlur(Channel_original, sigma)|
```

Pada implementasi:

```text
sigma = 2.0
block_size = 64 x 64
```

### Statistik Blok

```text
V_i          = var(noise_block_i)
mean_variance = mean(V_i)
variance_std  = std(V_i)
threshold     = mean_variance * 1.5
```

### Logika Threshold

```text
jika mean_variance < 2.0:
    noise terlalu rendah

jika variance_std > mean_variance * 1.5:
    noise antar blok tidak konsisten
```

---

## Slide 11 - Skor Keaslian Noise

### Kondisi 1: Gambar Terlalu Halus

```text
A_noise = max(10, mean_variance * 35)
```

Makna:

- Noise sangat rendah dapat mengindikasikan smoothing, retouching, blur, atau render digital.

### Kondisi 2: Noise Tidak Konsisten

```text
deviation_ratio = variance_std / (mean_variance * 1.5)

A_noise = max(20, 100 - (deviation_ratio * 20))
```

### Kondisi 3: Noise Stabil

```text
A_noise = 100
```

**Catatan pembicara:**

> Noise tidak digunakan sebagai vonis tunggal. Noise hanya menjadi sinyal pendukung karena kondisi pencahayaan, kamera, dan kompresi juga dapat memengaruhi pola noise.

---

## Slide 12 - Metode 3: Metadata Analysis

### Konsep Metadata

Metadata adalah informasi tambahan yang tersimpan di dalam file, misalnya:

- EXIF
- Merek dan model kamera
- Waktu pengambilan gambar
- Software yang digunakan
- Ukuran dan format file

### Alur Metadata

```text
Baca file dan EXIF
   |
   v
Cek kamera, software, dan timestamp
   |
   v
Cari anomali metadata
   |
   v
Hitung authenticity score
```

File: `python/analysis/metadata_analysis.py`

---

## Slide 13 - Rumus dan Threshold Metadata

### Skor Awal

```text
A_metadata = 100
```

### Penalti

```text
jika EXIF, kamera, software, dan timestamp tidak tersedia:
    A_metadata = A_metadata - 30

jika ditemukan software editor:
    A_metadata = A_metadata - 20

A_metadata = max(0, A_metadata)
```

### Interpretasi

| Skor Metadata | Verdict |
| --- | --- |
| `>= 85` | Kamera fisik real / otentik |
| `60 - 84` | Terindikasi editing / mencurigakan |
| `< 60` | Rekayasa digital / editing |

**Catatan penting:**

Metadata hilang tidak selalu berarti file palsu. WhatsApp, screenshot, media sosial, atau export ulang dapat menghapus metadata. Karena itu bobot metadata tidak dibuat paling besar.

---

## Slide 14 - Metode 4: Deepfake / GAN Fingerprint

### Konsep

Generator AI dapat meninggalkan pola frekuensi yang terlalu teratur akibat proses upsampling dan pembentukan piksel.

VERIDITY menggunakan analisis spektral untuk mencari:

- Varians profil frekuensi radial.
- Jumlah puncak spektral tidak wajar.
- Simetri frekuensi yang terlalu presisi.

### Alur

```text
Gambar RGB
   |
   v
Ubah menjadi grayscale
   |
   v
Fast Fourier Transform (FFT)
   |
   v
Hitung magnitude spectrum
   |
   v
Bangun radial profile
   |
   v
Hitung indikator GAN
   |
   v
gan_score dan A_ai
```

File: `python/analysis/deepfake_detector.py`

---

## Slide 15 - Rumus Deepfake / GAN

### Transformasi Frekuensi

```text
gray(x, y)    = mean(R, G, B)
F(u, v)       = FFT2(gray)
M(u, v)       = log(|fftshift(F(u, v))| + 1)
```

### Metrik Utama

```text
radial_variance   = var(radial_profile(M))
peak_count        = jumlah puncak spektral signifikan
quadrant_symmetry = std(quadrant_means) / mean(quadrant_means)
```

### Penambahan Skor GAN

```text
gan_score = 0

jika radial_variance > 1.5:
    gan_score += 0.3

jika peak_count > 5:
    gan_score += 0.4

jika quadrant_symmetry < 0.1:
    gan_score += 0.3
```

### Skor Keaslian AI

```text
A_ai = max(0, min(100, 100 - (gan_score * 100)))
```

---

## Slide 16 - Threshold Deepfake / GAN

| `gan_score` | Interpretasi Python |
| --- | --- |
| `<= 0.4` | Rendah / negatif |
| `> 0.4` | Mencurigakan |
| `> 0.5` | Dianggap positif deepfake oleh pipeline utama |
| `> 0.7` | Sangat tinggi |
| `> 0.85` | Laravel memberi status sangat berbahaya |

### Kenapa Ada Beberapa Threshold?

- Threshold `0.4` digunakan sebagai peringatan dini.
- Threshold `0.5` digunakan untuk keputusan positif deepfake.
- Threshold `0.7` digunakan sebagai label tingkat kemungkinan sangat tinggi.
- Threshold `0.85` digunakan Laravel sebagai batas sangat berbahaya.

**Catatan pembicara:**

> Metode deepfake pada VERIDITY adalah deteksi berbasis fingerprint spektral dan aturan, bukan model klasifikasi wajah yang dilatih sendiri. Sistem ini cocok sebagai indikator awal, tetapi masih perlu dataset validasi lebih luas.

---

## Slide 17 - Penentuan Bobot Foto

### Rumus Final Score

```text
FinalScore =
    (A_ela      * 0.30) +
    (A_noise    * 0.30) +
    (A_metadata * 0.20) +
    (A_ai       * 0.20)
```

### Alasan Bobot

| Metode | Bobot | Alasan |
| --- | ---: | --- |
| ELA | 30% | Membaca perbedaan jejak kompresi piksel |
| Noise | 30% | Membaca konsistensi residu piksel antar area |
| Metadata | 20% | Berguna, tetapi dapat hilang karena export biasa |
| AI/GAN | 20% | Menjadi indikator tambahan untuk pola generatif |

### Prinsip

- Total bobot = `100%`.
- ELA dan noise diberi bobot terbesar karena bekerja langsung pada struktur piksel.
- Metadata dan AI/GAN berfungsi sebagai penguat keputusan.

**Catatan penting:**

Bobot pada implementasi saat ini ditentukan secara heuristik berdasarkan peran setiap metode. Untuk penelitian lanjutan, bobot dapat dioptimalkan menggunakan dataset berlabel, confusion matrix, grid search, atau machine learning ensemble.

---

## Slide 18 - Contoh Perhitungan Final Foto

Misalnya hasil analisis:

```text
A_ela      = 82
A_noise    = 90
A_metadata = 70
A_ai       = 100
```

Maka:

```text
FinalScore =
    (82  * 0.30) +
    (90  * 0.30) +
    (70  * 0.20) +
    (100 * 0.20)

FinalScore =
    24.6 + 27 + 14 + 20

FinalScore = 85.6
```

Interpretasi awal Python:

```text
Jika gan_score <= 0.5,
metadata bukan "REKAYASA DIGITAL / EDITING",
dan ELA_anomaly <= 30,
maka hasil dapat dikategorikan AUTHENTIC.
```

---

## Slide 19 - Keputusan Akhir Foto di Python

### Verdict Dasar Python

```text
jika gan_score > 0.5:
    DEEPFAKE / AI GENERATED

elif FinalScore < 65
     atau metadata = REKAYASA DIGITAL / EDITING
     atau ELA_anomaly > 30:
    MANIPULATED

else:
    AUTHENTIC
```

### Kenapa Tidak Hanya Menggunakan FinalScore?

Karena beberapa indikator dianggap kritis:

- `gan_score > 0.5` dapat menunjukkan deepfake walaupun skor lain tinggi.
- `ELA_anomaly > 30` dapat menunjukkan area kompresi yang sangat tidak wajar.
- Metadata dengan verdict rekayasa digital menjadi tanda tambahan.

Konsep ini disebut **hard rule override**, yaitu kondisi kritis dapat mengalahkan skor rata-rata.

---

## Slide 20 - Keputusan Akhir Foto di Laravel

Laravel menerjemahkan hasil Python menjadi label yang lebih mudah dipahami user.

```text
jika gan_score > 0.5
atau verdict Python = DEEPFAKE / AI GENERATED:
    SANGAT BERBAHAYA (DEEPFAKE AI)

elif ELA_anomaly <= 5 dan gan_score <= 0.4:
    FOTO ASLI / JEPRETAN MURNI

elif FinalScore < 45
atau ELA_anomaly > 45
atau gan_score > 0.85
atau verdict Python = MANIPULATED:
    SANGAT BERBAHAYA

elif ELA_anomaly > 15
atau gan_score > 0.4
atau noise tidak konsisten dan ELA_anomaly > 8
atau metadata manipulatif:
    MENCURIGAKAN (TERINDIKASI REKAYASA)

else:
    FOTO ASLI / JEPRETAN MURNI
```

File:

- `veridity-laravel/app/Http/Controllers/Api/ForensicController.php`

**Catatan pembicara:**

> Python menghasilkan skor dan verdict teknis. Laravel menambahkan lapisan keputusan untuk pengalaman user, sehingga hasil dapat dibedakan menjadi aman, mencurigakan, sangat berbahaya, dan deepfake.

---

## Slide 21 - Alur Analisis Dokumen

```text
Upload PDF
   |
   v
Laravel mengirim file ke FastAPI
   |
   v
Python mengekstrak teks PDF
   |
   v
Normalisasi teks
   |
   v
Tokenisasi menjadi kalimat
   |
   v
Model roberta-base-openai-detector
   |
   v
Klasifikasi setiap kalimat
   |
   v
Hitung persentase Human, AI, dan Hybrid
   |
   v
Tentukan final_score dan label dokumen
   |
   v
Generate PDF report dengan highlight
```

File utama:

- `python/main_api.py`
- `python/analyze_document.py`
- `python/analysis/document_detector_core.py`
- `python/analysis/document_model_loaders.py`

---

## Slide 22 - Model AI untuk Dokumen

### Model yang Digunakan

```text
roberta-base-openai-detector
```

Model dipanggil melalui:

```python
pipeline("text-classification", model="roberta-base-openai-detector")
```

### Input dan Output Model

Input:

```text
Satu kalimat dari dokumen
```

Output:

```text
label = REAL atau FAKE
score = confidence model
```

### Threshold Confidence

```text
threshold = 0.8
```

Makna:

- Confidence `>= 0.8` dianggap kuat.
- Confidence `< 0.8` dianggap berada pada area campuran atau AI-refined.

**Catatan pembicara:**

> Pada bagian dokumen, VERIDITY benar-benar menggunakan pretrained AI model untuk inference. Sistem tidak melatih model dari nol, tetapi memakai model RoBERTa yang sudah tersedia lalu membangun logika klasifikasi dan pelaporan di atasnya.

---

## Slide 23 - Klasifikasi Kalimat Dokumen

### Aturan Klasifikasi

```text
jika label model = FAKE dan score >= 0.8:
    AI-generated

jika label model = FAKE dan score < 0.8:
    AI-generated & AI-refined

jika label model = REAL dan score >= 0.8:
    Human-written

jika label model = REAL dan score < 0.8:
    Human-written & AI-refined
```

### Kenapa Ada Kelas Hybrid?

- Tidak semua kalimat dapat dibedakan secara tegas.
- Kalimat dapat ditulis manusia lalu diperbaiki AI.
- Kalimat dapat dibuat AI lalu diedit manusia.
- Confidence di bawah `0.8` diperlakukan sebagai wilayah abu-abu.

---

## Slide 24 - Rumus Persentase Dokumen

Misalkan:

```text
N_total  = jumlah seluruh kalimat
N_human  = jumlah kalimat Human-written
N_ai     = jumlah kalimat AI-generated
N_hybrid = jumlah kalimat AI-refined / campuran
```

Rumus:

```text
HumanPercentage  = (N_human  / N_total) * 100
AIPercentage     = (N_ai     / N_total) * 100
HybridPercentage = (N_hybrid / N_total) * 100
```

Pada kode:

```text
human_p  = persentase Human-written
ai_p     = persentase AI-generated
hybrid_p = persentase AI-generated & AI-refined
         + persentase Human-written & AI-refined
```

### Skor Akhir Dokumen

```text
DocumentFinalScore = human_p
```

Makna:

Skor akhir dokumen menunjukkan seberapa besar kontribusi kalimat yang terdeteksi kuat sebagai tulisan manusia.

---

## Slide 25 - Threshold Hasil Dokumen

```text
jika DocumentFinalScore >= 80:
    AUTHENTIC (HUMAN WRITTEN)

elif DocumentFinalScore >= 60:
    MIXED TEXT (AI ASSISTED)

else:
    MAYORITAS AI GENERATED
```

| Human Score | Hasil |
| ---: | --- |
| `80% - 100%` | Authentic / Human Written |
| `60% - 79.99%` | Mixed Text / AI Assisted |
| `< 60%` | Mayoritas AI Generated |

### Catatan Interpretasi

`MAYORITAS AI GENERATED` tidak berarti semua teks 100% dibuat AI. Sistem tetap menampilkan bagian yang terdeteksi sebagai tulisan manusia dan bagian yang terdeteksi campuran.

---

## Slide 26 - Contoh Perhitungan Dokumen

Misalnya dokumen memiliki `20` kalimat:

```text
Human-written                = 13 kalimat
AI-generated                 = 4 kalimat
AI-generated & AI-refined    = 2 kalimat
Human-written & AI-refined   = 1 kalimat
```

Perhitungan:

```text
human_p  = (13 / 20) * 100 = 65%
ai_p     = (4  / 20) * 100 = 20%
hybrid_p = ((2 + 1) / 20) * 100 = 15%
```

Skor akhir:

```text
DocumentFinalScore = human_p = 65%
```

Hasil:

```text
MIXED TEXT (AI ASSISTED)
```

---

## Slide 27 - PDF Report dan Arsiran Dokumen

### Tujuan Arsiran

Arsiran membantu user melihat lokasi kalimat yang terindikasi AI atau campuran.

### Warna

| Warna | Makna |
| --- | --- |
| Merah muda | AI-generated |
| Jingga | AI-generated & AI-refined |
| Biru muda | Human-written & AI-refined |
| Tanpa warna | Human-written |

### Alur

```text
classification_map
   |
   v
Cari posisi kalimat pada PDF
   |
   v
Tambahkan highlight annotation
   |
   v
Tambahkan halaman ringkasan NLP metrics
   |
   v
Simpan PDF report
```

File: `python/analysis/document_pdf_utils.py`

Bagian administratif seperti cover, nama dosen, nama mahasiswa, nama kampus, dan lembar pengesahan dikecualikan dari arsiran.

---

## Slide 28 - OCR Tesseract sebagai Metode Pendukung

### Apa Itu Tesseract?

Tesseract adalah OCR atau Optical Character Recognition, yaitu teknologi untuk mengubah tulisan pada gambar menjadi teks.

### Penggunaan pada VERIDITY

Tesseract digunakan untuk membaca nota pembayaran dari aplikasi `distri`.

```text
Gambar nota
   |
   v
Tesseract OCR
   |
   v
Teks hasil OCR
   |
   v
Cocokkan rekening, nominal, tanggal, nama, dan channel pembayaran
```

Perintah:

```text
tesseract path_gambar stdout -l eng+ind
```

File:

- `veridity-laravel/app/Services/PaymentProofContentValidator.php`

---

## Slide 29 - Logika Validasi OCR

### Normalisasi

```text
normalized_text = lowercase(text_ocr)
digits_text     = hapus seluruh karakter selain angka
```

### Pemeriksaan

```text
rekening_cocok = nomor rekening tujuan ada di digits_text
nominal_cocok  = nominal checkout ada di digits_text
tanggal_cocok  = pola tanggal ditemukan
nama_cocok     = token nama penerima ditemukan
channel_cocok  = metode pembayaran ditemukan
```

### Keputusan

```text
jika rekening atau nominal gagal:
    failed

elif beberapa detail tidak jelas:
    review_required

else:
    passed
```

Tesseract bukan detektor edit gambar. Tesseract adalah metode pendukung untuk membaca dan memvalidasi isi teks pada nota.

---

## Slide 30 - Penyimpanan Hasil di Laravel

Setelah Python mengembalikan JSON, Laravel menyimpan:

- Nama file
- Path file
- ELA score
- Status deepfake
- Metadata details
- Noise status
- Final result
- Path PDF report

Model:

```text
veridity-laravel/app/Models/ForensicAnalysis.php
```

Controller:

```text
veridity-laravel/app/Http/Controllers/Api/ForensicController.php
```

Manfaat:

- Hasil dapat dibuka kembali melalui riwayat.
- Website dan mobile menampilkan data yang sama.
- PDF report tidak perlu dibuat ulang setiap kali diunduh.

---

## Slide 31 - Mengapa Menggunakan Multi-Metode?

### Kelemahan Jika Hanya Satu Metode

| Metode | Keterbatasan |
| --- | --- |
| ELA | Dipengaruhi kompresi ulang dan format file |
| Noise | Dipengaruhi kamera, cahaya, blur, dan kompresi |
| Metadata | Dapat hilang karena aplikasi chat atau export |
| GAN Fingerprint | Tidak semua generator AI meninggalkan pola sama |
| NLP Detector | Sensitif terhadap bahasa, panjang teks, dan gaya penulisan |
| OCR | Sensitif terhadap kualitas gambar |

### Keuntungan Gabungan

- Satu metode dapat menguatkan atau melemahkan metode lain.
- Kondisi kritis dapat menggunakan hard rule override.
- Hasil lebih mudah dijelaskan karena memiliki bukti visual dan numerik.

---

## Slide 32 - Jenis Kecerdasan Buatan pada VERIDITY

### Komponen AI / Kecerdasan Buatan

1. **Pretrained NLP Model**
   - `roberta-base-openai-detector`
   - Mengklasifikasikan kalimat sebagai REAL atau FAKE.

2. **Rule-Based Intelligent Decision**
   - Menggunakan threshold dan bobot untuk menggabungkan indikator foto.

3. **Computer Vision Forensic**
   - ELA, noise map, metadata, dan analisis spektral.

4. **OCR**
   - Tesseract membaca teks dari gambar nota.

### Karakter Sistem

VERIDITY adalah sistem **hybrid**, yaitu menggabungkan:

- model AI pretrained;
- metode statistik;
- computer vision;
- rule-based decision system.

---

## Slide 33 - Evaluasi yang Perlu Dilakukan

Untuk mengukur performa secara ilmiah, dibutuhkan dataset berlabel:

- Foto asli
- Foto editan
- Foto AI-generated
- Dokumen manusia
- Dokumen AI
- Dokumen campuran

### Metrik Evaluasi

```text
Accuracy  = (TP + TN) / (TP + TN + FP + FN)
Precision = TP / (TP + FP)
Recall    = TP / (TP + FN)
F1-Score  = 2 * (Precision * Recall) / (Precision + Recall)
```

### Confusion Matrix

| Aktual / Prediksi | Positif | Negatif |
| --- | ---: | ---: |
| Positif | True Positive | False Negative |
| Negatif | False Positive | True Negative |

**Catatan pembicara:**

> Threshold dan bobot saat ini adalah implementasi awal berbasis heuristik. Tahap berikutnya adalah menguji banyak data, membuat confusion matrix, lalu menyesuaikan threshold agar false positive dan false negative berkurang.

---

## Slide 34 - Keterbatasan Sistem

- Hasil merupakan indikasi awal, bukan bukti hukum mutlak.
- Foto yang sudah dikompresi berkali-kali dapat memengaruhi ELA dan metadata.
- Kamera dan kondisi cahaya dapat memengaruhi noise.
- Generator AI baru dapat memiliki fingerprint berbeda.
- Model NLP dapat kurang akurat pada bahasa atau gaya tertentu.
- Dokumen yang terlalu pendek tidak cukup untuk dianalisis.
- OCR dapat gagal pada gambar blur, miring, atau gelap.

### Prinsip Penggunaan

```text
Hasil VERIDITY = alat bantu investigasi
bukan satu-satunya dasar keputusan
```

---

## Slide 35 - Skenario Demo

### Demo Foto

1. Upload foto.
2. Tampilkan loading analisis.
3. Buka hasil akhir.
4. Tampilkan foto asli, ELA map, dan noise map.
5. Jelaskan ELA score, GAN score, metadata, dan final score.
6. Unduh PDF report.

### Demo Dokumen

1. Upload PDF.
2. Tampilkan hasil Human, Mixed, atau Mayoritas AI.
3. Buka mode detail NLP metrics.
4. Unduh PDF report.
5. Tunjukkan arsiran kalimat.

### Demo Integrasi

1. Upload nota dari `distri`.
2. Jelaskan analisis visual dan OCR Tesseract.
3. Tampilkan hasil validasi pembayaran.

---

## Slide 36 - Contoh Narasi Demo Foto

> Pada foto ini, sistem pertama kali membaca metadata untuk melihat apakah ada informasi kamera atau jejak software editor. Setelah itu, Python menjalankan ELA untuk mengukur perbedaan kompresi piksel, noise analysis untuk melihat konsistensi residu antar area, dan GAN fingerprint untuk mencari pola spektral buatan AI. Setiap metode menghasilkan skor keaslian. Skor tersebut digabungkan menggunakan bobot 30 persen ELA, 30 persen noise, 20 persen metadata, dan 20 persen AI detection. Jika ada indikator kritis seperti GAN score di atas 0.5, sistem dapat langsung memberi label deepfake walaupun skor metode lain masih tinggi.

---

## Slide 37 - Contoh Narasi Demo Dokumen

> Pada dokumen, Laravel mengirim file PDF ke FastAPI Python. Python mengekstrak teks, menormalisasi teks, lalu memecahnya menjadi kalimat. Setiap kalimat diperiksa menggunakan model RoBERTa AI text detector. Confidence model dibandingkan dengan threshold 0.8 untuk membedakan prediksi kuat dan area campuran. Setelah itu sistem menghitung persentase Human-written, AI-generated, dan Hybrid. Skor akhir dokumen adalah persentase Human-written. Jika nilainya di atas atau sama dengan 80 persen, dokumen dianggap dominan tulisan manusia. Jika 60 sampai 79 persen, hasilnya mixed. Jika di bawah 60 persen, hasilnya mayoritas AI generated.

---

## Slide 38 - Kesimpulan

### Kesimpulan

- VERIDITY menggabungkan Laravel dan Python dalam satu sistem analisis.
- Analisis foto memakai ELA, noise, metadata, dan GAN fingerprint.
- Analisis dokumen memakai pretrained NLP model dan klasifikasi per kalimat.
- Threshold digunakan untuk membedakan kondisi aman, mencurigakan, dan berbahaya.
- Bobot digunakan untuk menggabungkan beberapa skor menjadi hasil akhir.
- PDF report memberikan bukti visual dan rincian komputasi.

### Nilai Utama

```text
VERIDITY tidak hanya memberi label,
tetapi juga menjelaskan alasan di balik label tersebut.
```

---

## Slide 39 - Pertanyaan dan Jawaban

### Terima Kasih

**Pertanyaan?**

---

# Lampiran A - Ringkasan Rumus

## Rumus Foto

```text
D(x, y) = |I_original(x, y) - I_compressed(x, y)|

ELA_anomaly = mean(D) + 2 * std(D)

A_ela = max(0, min(100, 100 - (ELA_anomaly * 3)))

N_channel = |Channel_original - GaussianBlur(Channel_original, sigma)|

V_i = var(noise_block_i)

mean_variance = mean(V_i)

variance_std = std(V_i)

deviation_ratio = variance_std / (mean_variance * 1.5)

A_noise = max(20, 100 - (deviation_ratio * 20))

A_metadata = 100 - metadata_penalty

A_ai = max(0, min(100, 100 - (gan_score * 100)))

FinalScore =
    (A_ela * 0.30) +
    (A_noise * 0.30) +
    (A_metadata * 0.20) +
    (A_ai * 0.20)
```

## Rumus Dokumen

```text
P_kelas = (jumlah_kalimat_kelas / total_kalimat) * 100

human_p  = persentase Human-written
ai_p     = persentase AI-generated
hybrid_p = persentase seluruh kelas AI-refined

DocumentFinalScore = human_p
```

---

# Lampiran B - Ringkasan Threshold

## Threshold Foto

| Parameter | Threshold | Fungsi |
| --- | ---: | --- |
| ELA mask pixel | `40` | Membuat mask visual ELA |
| ELA aman kuat | `<= 5` | Mendukung foto asli |
| ELA + noise warning | `> 8` | Warning jika noise tidak konsisten |
| ELA mencurigakan | `> 15` | Label warning Laravel |
| ELA manipulasi Python | `> 30` | Hard rule manipulasi |
| ELA sangat berbahaya | `> 45` | Label danger Laravel |
| Noise terlalu rendah | `< 2.0` | Indikasi smoothing |
| Noise tidak konsisten | `std > mean * 1.5` | Variasi antar blok tinggi |
| GAN warning | `> 0.4` | Mencurigakan |
| GAN positif | `> 0.5` | Deepfake / AI generated |
| GAN sangat tinggi | `> 0.7` | Likelihood sangat tinggi |
| GAN danger Laravel | `> 0.85` | Sangat berbahaya |
| Final score manipulasi Python | `< 65` | Verdict manipulated |
| Final score danger Laravel | `< 45` | Label sangat berbahaya |

## Threshold Dokumen

| Parameter | Threshold | Fungsi |
| --- | ---: | --- |
| Confidence model | `0.8` | Memisahkan prediksi kuat dan AI-refined |
| Human score aman | `>= 80%` | Authentic / Human Written |
| Human score mixed | `>= 60%` | Mixed Text / AI Assisted |
| Human score AI mayoritas | `< 60%` | Mayoritas AI Generated |

---

# Lampiran C - Pertanyaan yang Mungkin Ditanyakan Dosen

## Kenapa bobot ELA dan noise lebih besar?

Karena ELA dan noise membaca karakter piksel secara langsung. Metadata dapat hilang karena export biasa, sedangkan GAN fingerprint menjadi indikator tambahan yang belum selalu muncul pada semua generator AI.

## Apakah bobot 30%, 30%, 20%, 20% sudah paling optimal?

Belum tentu. Bobot saat ini adalah bobot heuristik pada implementasi awal. Untuk optimasi, diperlukan dataset berlabel dan evaluasi menggunakan accuracy, precision, recall, F1-score, serta confusion matrix.

## Kenapa menggunakan threshold?

Threshold mengubah nilai kontinu menjadi keputusan yang dapat dipahami. Contohnya, `gan_score > 0.5` digunakan sebagai batas positif deepfake, sedangkan confidence NLP `>= 0.8` digunakan sebagai prediksi yang dianggap kuat.

## Kenapa ada hard rule override?

Karena nilai rata-rata dapat menutupi indikator kritis. Contohnya, foto dapat memiliki metadata bagus tetapi memiliki `gan_score` tinggi. Pada kondisi ini, indikator deepfake harus lebih diprioritaskan.

## Apakah sistem menggunakan machine learning?

Ya, pada analisis dokumen sistem menggunakan pretrained model `roberta-base-openai-detector`. Pada foto, sistem menggunakan metode computer vision, statistik, analisis spektral, dan rule-based decision system.

## Apakah VERIDITY dapat memastikan file palsu 100%?

Tidak. VERIDITY adalah alat bantu analisis forensik awal. Hasil perlu dikombinasikan dengan pemeriksaan manusia, sumber file, konteks, dan bukti lain.

## Kenapa PDF lebih disarankan untuk dokumen?

PDF menyimpan posisi teks pada halaman, sehingga sistem dapat mencari koordinat kalimat dan menambahkan highlight. Format lain seperti DOCX memiliki layout yang lebih dinamis sehingga hasil konversi dapat berubah.

## Apa peran Laravel pada sistem AI?

Laravel tidak menjalankan komputasi AI utama. Laravel bertugas menerima request, memvalidasi file, memanggil Python, menerjemahkan hasil teknis menjadi label user, menyimpan hasil, dan menyediakan riwayat serta laporan.

## Apa peran FastAPI?

FastAPI menyediakan endpoint Python untuk analisis dokumen dan pembuatan PDF report. Laravel mengirim file ke endpoint tersebut dan menerima hasil dalam format JSON atau PDF.
