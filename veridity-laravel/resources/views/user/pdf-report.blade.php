<!DOCTYPE html>
<html>

<head>
    <title>Laporan Investigasi Forensik Veridity</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            line-height: 1.5;
            font-size: 12px;
        }

        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .title {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #64748b;
            uppercase;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            color: white;
            display: inline-block;
            font-size: 11px;
        }

        .bg-success {
            bg-color: #16a34a;
            background-color: #16a34a;
        }

        .bg-warning {
            bg-color: #ea580c;
            background-color: #ea580c;
        }

        .bg-danger {
            bg-color: #dc2626;
            background-color: #dc2626;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
            border-left: 3px solid #3b82f6;
            padding-left: 8px;
            margin-top: 25px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid #e2e8f0;
        }

        th {
            background-color: #f8fafc;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #475569;
        }

        td {
            padding: 8px;
            color: #334155;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    {{-- KOP SURAT FORMAL LAPORAN DETEKSI --}}
    <table style="width: 100%; border: none; margin-bottom: 15px;">
        <tr style="border: none;">
            <td style="border: none; width: 50%; padding: 0;" class="logo">VeriDity.</td>
            <td style="border: none; width: 50%; padding: 0;" class="title">LAPORAN INTEGRITAS DIGITAL</td>
        </tr>
    </table>
    <div class="header"></div>

    {{-- DETAIL BERKAS MASTER --}}
    <div class="section-title">Informasi Dokumen Barang Bukti</div>
    <table>
        <tr>
            <th style="width: 30%;">Kode Investigasi</th>
            <td>#VRD-{{ $analysis->id }}</td>
        </tr>
        <tr>
            <th>Nama Berkas Asli</th>
            <td>{{ $analysis->image_name }}</td>
        </tr>
        <tr>
            <th>Waktu Analisis</th>
            <td>{{ $waktuAnalisis }}</td> {{-- Menggunakan variabel hasil konversi setTimezone --}}
        </tr>
        <tr>
            <th>Format Objek</th>
            <td>{{ strtoupper($fileExtension) }} (Rumpun {{ $isDocument ? 'Linguistic Teks' : 'Multimedia Citra' }})
            </td>
        </tr>
        <tr>
            <th>Vonis Akhir Berkas</th>
            <td>
                <span
                    class="badge {{ $analysis->final_result['summary_color'] == 'success' ? 'bg-success' : ($analysis->final_result['summary_color'] == 'warning' ? 'bg-warning' : 'bg-danger') }}">
                    {{ $analysis->final_result['summary_label'] }}
                </span>
            </td>
        </tr>
    </table>

    {{-- KONDISIONAL PERFORMA MATRIKS RISET --}}
    @if ($isDocument)
        {{-- BLOK PRINT DATA KHUSUS DOKUMEN TEKS --}}
        <div class="section-title">Rincian Komputasi Bahasa (NLP Metrics)</div>
        <p>Berdasarkan hasil parsing sebaran kalimat menggunakan *RoBERTa-Base OpenAI Text Classification Pipeline*,
            berikut adalah probabilitas sebaran susunan kata:</p>
        <table>
            <thead>
                <tr>
                    <th>Komponen Matriks Linguistik</th>
                    <th>Kontribusi Persentase</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kalimat Orisinal (Murni Buatan Manusia)</td>
                    <td>{{ $analysis->final_result['full_report']['results']['document']['metrics']['human_p'] ?? 0 }}%
                    </td>
                </tr>
                <tr>
                    <td>Kalimat Sintetis (Full AI Generated ChatGPT/Sejenisnya)</td>
                    <td>{{ $analysis->final_result['full_report']['results']['document']['metrics']['ai_p'] ?? 0 }}%
                    </td>
                </tr>
                <tr>
                    <td>Kalimat Modifikasi (Hybrid Paraphrased / AI-Refined)</td>
                    <td>{{ $analysis->final_result['full_report']['results']['document']['metrics']['hybrid_p'] ?? 0 }}%
                    </td>
                </tr>
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td>SKOR TOTAL ORISINALITAS BAHASA (HUMAN SCORE)</td>
                    <td style="color: #1e3a8a;">{{ $analysis->final_result['full_report']['final_score'] ?? 0 }}%</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Interpretasi Pakar Forensik</div>
        <p
            style="background-color: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-style: italic;">
            "{{ $analysis->final_result['full_report']['results']['document']['interpretation'] ?? '' }}"
        </p>
    @else
        {{-- BLOK PRINT DATA KHUSUS CITRA GAMBAR --}}
        <div class="section-title">Kalkulasi Parameter Fisik Citra Digital</div>
        <table>
            <thead>
                <tr>
                    <th>Metode Forensik Multimedia</th>
                    <th>Skor Parameter Mentah</th>
                    <th>Nilai Integritas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Error Level Analysis (ELA)</td>
                    <td>{{ number_format($analysis->ela_score, 4) }}%</td>
                    <td>{{ number_format(max(0, 100 - $analysis->ela_score * 3), 2) }}%</td>
                </tr>
                <tr>
                    <td>Spectral Deepfake (GAN Score)</td>
                    <td>{{ number_format($analysis->final_result['full_report']['results']['ai_detection']['metrics']['gan_score'] ?? 0, 4) }}
                    </td>
                    <td>{{ number_format(100 - ($analysis->final_result['full_report']['results']['ai_detection']['metrics']['gan_score'] ?? 0) * 100, 2) }}%
                    </td>
                </tr>
                <tr>
                    <td>EXIF Metadata Security</td>
                    <td>{{ $analysis->final_result['full_report']['results']['metadata']['summary']['verdict'] ?? 'N/A' }}
                    </td>
                    <td>{{ number_format($analysis->final_result['full_report']['results']['metadata']['summary']['authenticity_score'] ?? 100, 2) }}%
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Hasil Pemeriksaan Partikel Kebisingan Lensa (Noise Interpretation)</div>
        <p
            style="background-color: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-style: italic;">
            "{{ $analysis->final_result['full_report']['results']['noise']['interpretation'] ?? 'Normal.' }}"
        </p>
    @endif

    {{-- FOOTER NOTIFIKASI VALIDASI KAMPUS --}}
    <div class="footer">
        Dokumen ini diterbitkan secara otomatis oleh sistem Veridity Platform Forensik. Dicetak pada
        {{ $generatedAt }}.<br>
        <strong>Politeknik Elektronika Negeri Surabaya (PENS) - Jurusan Teknik Informatika</strong>
    </div>

</body>

</html>
