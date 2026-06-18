@php
    $isId = ($language ?? 'en') === 'id';
    $t = fn (string $en, string $id) => $isId ? $id : $en;
    $analysisText = function (?string $value) use ($isId) {
        $value = (string) ($value ?? '');
        if ($isId || trim($value) === '') {
            return $value;
        }

        $normalized = strtolower($value);
        if (str_contains($normalized, 'kamera fisik real') || str_contains($normalized, 'otentik')) {
            return 'Physical camera capture (authentic)';
        }
        if (str_contains($normalized, 'rekayasa digital') || str_contains($normalized, 'editing')) {
            return 'Digital manipulation / editing indicated';
        }
        if (str_contains($normalized, 'noise')) {
            if (str_contains($normalized, 'tidak') || str_contains($normalized, 'anomali')) {
                return 'Noise pattern needs review as a supporting forensic signal.';
            }

            return 'Noise pattern remains within the final tolerance range.';
        }

        return $value;
    };
    $objectFamily = $isDocument
        ? $t('Linguistic Text', 'Linguistic Teks')
        : $t('Image Multimedia', 'Multimedia Citra');
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>{{ $t('Veridity Forensic Investigation Report', 'Laporan Investigasi Forensik Veridity') }}</title>
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
            <td style="border: none; width: 50%; padding: 0;" class="title">{{ $t('DIGITAL INTEGRITY REPORT', 'LAPORAN INTEGRITAS DIGITAL') }}</td>
        </tr>
    </table>
    <div class="header"></div>

    {{-- DETAIL BERKAS MASTER --}}
    <div class="section-title">{{ $t('Evidence File Information', 'Informasi Dokumen Barang Bukti') }}</div>
    <table>
        <tr>
            <th style="width: 30%;">{{ $t('Investigation Code', 'Kode Investigasi') }}</th>
            <td>#VRD-{{ $analysis->id }}</td>
        </tr>
        <tr>
            <th>{{ $t('Original File Name', 'Nama Berkas Asli') }}</th>
            <td>{{ $analysis->image_name }}</td>
        </tr>
        <tr>
            <th>{{ $t('Analysis Time', 'Waktu Analisis') }}</th>
            <td>{{ $waktuAnalisis }}</td> {{-- Menggunakan variabel hasil konversi setTimezone --}}
        </tr>
        <tr>
            <th>{{ $t('Object Format', 'Format Objek') }}</th>
            <td>{{ strtoupper($fileExtension) }} ({{ $t('Family', 'Rumpun') }} {{ $objectFamily }})
            </td>
        </tr>
        <tr>
            <th>{{ $t('Final File Verdict', 'Vonis Akhir Berkas') }}</th>
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
        <div class="section-title">{{ $t('Language Computation Details (NLP Metrics)', 'Rincian Komputasi Bahasa (NLP Metrics)') }}</div>
        <p>{{ $t(
            'Based on sentence distribution parsing using the RoBERTa-Base OpenAI Text Classification Pipeline, the following probabilities describe the wording distribution:',
            'Berdasarkan hasil parsing sebaran kalimat menggunakan *RoBERTa-Base OpenAI Text Classification Pipeline*, berikut adalah probabilitas sebaran susunan kata:'
        ) }}</p>
        <table>
            <thead>
                <tr>
                    <th>{{ $t('Linguistic Matrix Component', 'Komponen Matriks Linguistik') }}</th>
                    <th>{{ $t('Percentage Contribution', 'Kontribusi Persentase') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $t('Original Sentences (Human-written)', 'Kalimat Orisinal (Murni Buatan Manusia)') }}</td>
                    <td>{{ $analysis->final_result['full_report']['results']['document']['metrics']['human_p'] ?? 0 }}%
                    </td>
                </tr>
                <tr>
                    <td>{{ $t('Synthetic Sentences (Fully AI-generated)', 'Kalimat Sintetis (Full AI Generated ChatGPT/Sejenisnya)') }}</td>
                    <td>{{ $analysis->final_result['full_report']['results']['document']['metrics']['ai_p'] ?? 0 }}%
                    </td>
                </tr>
                <tr>
                    <td>{{ $t('Modified Sentences (Hybrid Paraphrased / AI-Refined)', 'Kalimat Modifikasi (Hybrid Paraphrased / AI-Refined)') }}</td>
                    <td>{{ $analysis->final_result['full_report']['results']['document']['metrics']['hybrid_p'] ?? 0 }}%
                    </td>
                </tr>
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td>{{ $t('TOTAL LANGUAGE ORIGINALITY SCORE (HUMAN SCORE)', 'SKOR TOTAL ORISINALITAS BAHASA (HUMAN SCORE)') }}</td>
                    <td style="color: #1e3a8a;">{{ $analysis->final_result['full_report']['final_score'] ?? 0 }}%</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">{{ $t('Forensic Expert Interpretation', 'Interpretasi Pakar Forensik') }}</div>
        <p
            style="background-color: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-style: italic;">
            "{{ $analysis->final_result['full_report']['results']['document']['interpretation'] ?? '' }}"
        </p>

        <div class="section-title">{{ $t('Conclusion Rationale and Decision Formula', 'Alasan Kesimpulan dan Rumus Keputusan') }}</div>
        <table>
            <tr>
                <th>Rumus Human Score</th>
                <td>{{ $t('Human Score = percentage of sentences classified as Human-written.', 'Human Score = persentase kalimat yang diklasifikasikan sebagai Human-written.') }}</td>
            </tr>
            <tr>
                <th>{{ $t('Safe Threshold', 'Ambang Aman') }}</th>
                <td>{{ $t('>= 80% Human Score is categorized as AUTHENTIC (HUMAN WRITTEN).', '>= 80% Human Score dikategorikan AUTHENTIC (HUMAN WRITTEN).') }}</td>
            </tr>
            <tr>
                <th>{{ $t('Mixed Threshold', 'Ambang Mixed') }}</th>
                <td>{{ $t('60% - 79% Human Score is categorized as MIXED TEXT (AI ASSISTED).', '60% - 79% Human Score dikategorikan MIXED TEXT (AI ASSISTED).') }}</td>
            </tr>
            <tr>
                <th>{{ $t('Mostly-AI Threshold', 'Ambang Mayoritas AI') }}</th>
                <td>{{ $t('< 60% Human Score is categorized as MOSTLY AI GENERATED.', '< 60% Human Score dikategorikan MAYORITAS AI GENERATED.') }}</td>
            </tr>
        </table>
    @else
        {{-- BLOK PRINT DATA KHUSUS CITRA GAMBAR --}}
        <div class="section-title">{{ $t('Digital Image Physical Parameter Calculation', 'Kalkulasi Parameter Fisik Citra Digital') }}</div>
        <table>
            <thead>
                <tr>
                    <th>{{ $t('Multimedia Forensic Method', 'Metode Forensik Multimedia') }}</th>
                    <th>{{ $t('Raw Parameter Score', 'Skor Parameter Mentah') }}</th>
                    <th>{{ $t('Integrity Value', 'Nilai Integritas') }}</th>
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
                    <td>{{ $analysisText($analysis->final_result['full_report']['results']['metadata']['summary']['verdict'] ?? 'N/A') }}
                    </td>
                    <td>{{ number_format($analysis->final_result['full_report']['results']['metadata']['summary']['authenticity_score'] ?? 100, 2) }}%
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">{{ $t('Lens Noise Particle Inspection Result (Noise Interpretation)', 'Hasil Pemeriksaan Partikel Kebisingan Lensa (Noise Interpretation)') }}</div>
        <p
            style="background-color: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; font-style: italic;">
            "{{ $analysisText($analysis->final_result['full_report']['results']['noise']['interpretation'] ?? 'Normal.') }}"
        </p>

        <div class="section-title">{{ $t('Image Forensic Conclusion Rationale', 'Alasan Kesimpulan Forensik Citra') }}</div>
        <table>
            <tr>
                <th>ELA</th>
                <td>{{ $t('Lower ELA anomaly means the image compression traces are more consistent.', 'Semakin kecil anomali ELA, semakin konsisten jejak kompresi piksel pada gambar.') }}</td>
            </tr>
            <tr>
                <th>Deepfake/GAN</th>
                <td>{{ $t('A low GAN score indicates AI-synthetic image traces are not dominant.', 'GAN score rendah menunjukkan indikasi citra sintetis AI tidak dominan.') }}</td>
            </tr>
            <tr>
                <th>Noise</th>
                <td>{{ $t('Noise is treated as a supporting signal and correlated with ELA, metadata, and AI detection.', 'Noise dinilai sebagai sinyal pendukung dan dikorelasikan dengan ELA, metadata, dan AI detection.') }}</td>
            </tr>
            <tr>
                <th>Metadata</th>
                <td>{{ $t('Metadata is used to inspect device traces, editor applications, and file creation-time consistency.', 'Metadata dipakai untuk melihat jejak perangkat, aplikasi penyunting, serta konsistensi waktu pembuatan file.') }}</td>
            </tr>
        </table>
    @endif

    {{-- FOOTER NOTIFIKASI VALIDASI KAMPUS --}}
    <div class="footer">
        {{ $t('This document was automatically issued by the Veridity Forensic Platform. Printed on', 'Dokumen ini diterbitkan secara otomatis oleh sistem Veridity Platform Forensik. Dicetak pada') }}
        {{ $generatedAt }}.<br>
        <strong>{{ $t('Politeknik Elektronika Negeri Surabaya (PENS) - Informatics Engineering Department', 'Politeknik Elektronika Negeri Surabaya (PENS) - Jurusan Teknik Informatika') }}</strong>
    </div>

</body>

</html>
