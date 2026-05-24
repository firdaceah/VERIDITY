from PIL import Image, ImageFilter, ImageChops
from pathlib import Path
import numpy as np
import os

def generate_noise_map(image_path, sigma=2.0, ela_anomaly_score=0.0, is_deepfake_positive=False):
    """
    Menghasilkan noise map dengan mendeteksi komponen frekuensi tinggi.
    Telah dioptimasi untuk mengeliminasi false positive pada tekstur alami 
    dengan mengintegrasikan referensi silang dari Skor ELA dan status Deepfake.
    """
    try:
        img = Image.open(image_path).convert('RGB')
        
        # 1. Proses Pemisahan Channel Warna (RGB)
        channels = img.split()
        noise_maps = []
        variance_per_channel = []

        for channel in channels:
            # Terapkan Gaussian Blur untuk memisahkan komponen frekuensi rendah
            blurred = channel.filter(ImageFilter.GaussianBlur(radius=sigma))

            # Hitung perbedaan warna (High-pass filter untuk mengambil partikel noise halus)
            diff = ImageChops.difference(channel, blurred)

            # Perkuat kontras visual noise
            enhanced = ImageChops.multiply(diff, diff)
            noise_maps.append(enhanced)

            # Hitung varians tingkat kebisingan (noise) pada channel ini
            noise_array = np.array(diff)
            variance_per_channel.append(float(np.var(noise_array)))

        # Gabungkan kembali menjadi satu kesatuan gambar RGB Noise Map
        noise_map = Image.merge('RGB', noise_maps)

        # 2. Analisis Konsistensi Kebisingan Menggunakan Metode Berbasis Blok (64x64)
        noise_array_full = np.array(noise_map)
        h, w = noise_array_full.shape[:2]
        block_size = 64
        block_variances = []

        for i in range(0, h - block_size, block_size):
            for j in range(0, w - block_size, block_size):
                block = noise_array_full[i:i+block_size, j:j+block_size]
                block_variances.append(np.var(block))

        # Kalkulasi statistik dasar partikel gambar
        mean_variance = np.mean(block_variances) if block_variances else 0
        variance_std = float(np.std(block_variances)) if block_variances else 0

        # 3. INTERSEPTOR LOGIKA PENENTUAN WARNINGS & INTERPRETASI (ANTI-SALAH PREDIKSI)
        warnings = []
        researcher_note = "Sensor High-Pass Noise-Grid menunjukkan sebaran partikel normal dan homogen khas lensa optik."

        # KASUS A: Jika gambar terindikasi murni hasil rekayasa AI / GAN
        if is_deepfake_positive:
            interpretation = "Kadar noise sangat rendah (Absennya Grain Alami). Hal ini merupakan karakteristik utama dari citra sintetik buatan generator AI."
            warnings.append("Kadar noise sangat rendah - Karakteristik utama citra buatan AI/GAN")
            researcher_note = "Varians noise mendekati nol di semua channel warna karena gambar dibentuk melalui komputasi piksel matematika AI, bukan sensor optik lensa."
            
        else:
            # Pengecekan 1: Jika gambar terlalu mulus akibat editing manual (Smoothing/Retouching)
            if mean_variance < 2.0:
                warnings.append("Kadar noise sangat rendah - Kemungkinan dilakukan manipulasi penghalusan objek (Retouching/Smoothing)")
                interpretation = "Kadar noise sangat rendah - Terdeteksi adanya manipulasi penghalusan objek lokal (Retouching/Smoothing)."
                researcher_note = "Penghapusan residu noise secara ekstrem mengindikasikan penggunaan patch tool atau efek blur penyuntingan."
            
            # Pengecekan 2: Konsistensi sebaran partikel (Splicing / Tempelan Objek)
            else:
                # Set ambang batas toleransi ketidakrataan secara dinamis
                # Jika Gambar Asli (Skor ELA sangat rendah < 5), kita lunturkan keketatan threshold-nya (2.5) 
                # agar tidak gampang menuduh palsu pada gambar yang memiliki gradasi warna tajam secara alami.
                threshold_multiplier = 2.5 if ela_anomaly_score <= 5.0 else 1.2
                
                if variance_std > (mean_variance * threshold_multiplier):
                    # ---- REVISI FORMULASI BAHASA: 100% UNIVERSAL UNTUK SEMUA JENIS GAMBAR ----
                    if ela_anomaly_score <= 5.0:
                        interpretation = "Kualitas sebaran partikel gambar dinilai wajar dan konsisten. Perubahan kontras piksel yang terdeteksi merupakan karakteristik alami dari struktur citra ini."
                        researcher_note = "Varians lokal terdeteksi pada area citra, namun diklasifikasikan sebagai gradasi orisinal karena integritas level kompresi ELA homogen."
                    else:
                        warnings.append("Pola sebaran partikel gambar tidak rata. Terdeteksi adanya kontaminasi objek asing (Splicing)")
                        interpretation = "Pola sebaran partikel gambar tidak rata. Terdeteksi adanya objek asing yang sengaja ditempel atau dihapus (Splicing)."
                        researcher_note = "Terdapat anomali ketajaman lokal yang kontras pada batas segmen objek, divalidasi oleh tingginya nilai anomali kompresi ELA."
                else:
                    interpretation = "Kualitas partikel gambar tersebar merata. Tidak ditemukan tanda-tanda bekas editan atau tempelan objek pada citra ini."

        return {
            'status': 'success',
            'noise_map': noise_map,
            'metrics': {
                'channel_noise_variance': {
                    'red': round(variance_per_channel[0], 4) if len(variance_per_channel) > 0 else 0,
                    'green': round(variance_per_channel[1], 4) if len(variance_per_channel) > 1 else 0,
                    'blue': round(variance_per_channel[2], 4) if len(variance_per_channel) > 2 else 0
                },
                'overall_variance': round(float(mean_variance), 4),
                'block_variance_std': round(variance_std, 4),
                'blocks_analyzed': len(block_variances)
            },
            'warnings': warnings,
            'interpretation': interpretation,
            'researcher_note': researcher_note
        }

    except Exception as e:
        return {
            'status': 'error',
            'error': str(e),
            'noise_map': None
        }