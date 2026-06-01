from PIL import Image, ImageFilter, ImageChops
from pathlib import Path
import numpy as np
import os

def generate_noise_map(image_path, sigma=2.0, ela_anomaly_score=0.0, is_deepfake_positive=False):
    """
    MODE EKSKERIMEN MANDIRI: Menghasilkan noise map secara independen.
    Seluruh sistem interseptor pelonggaran silang dinonaktifkan untuk menguji
    respons murni high-pass filter terhadap sebaran piksel gambar.
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

        # 3. PENILAIAN MANDIRI TANPA PENGARUH VARIABEL LUAR
        warnings = []
        researcher_note = "Sensor High-Pass Noise-Grid mengevaluasi densitas partikel citra secara independen."
        
        # Mengunci threshold multiplier pada nilai standar riset forensik citra (1.5)
        static_threshold_multiplier = 1.5

        # Pengecekan A: Jika gambar terlalu mulus/bersih (Sintetis AI atau Manual Blur)
        if mean_variance < 2.0:
            warnings.append("Kadar noise sangat rendah - Kemungkinan dilakukan manipulasi penghalusan objek (Retouching/Smoothing)")
            interpretation = "Kadar noise sangat rendah - Terdeteksi adanya manipulasi penghalusan objek lokal (Retouching/Smoothing)."
            researcher_note = "Penghapusan residu noise secara ekstrem mengindikasikan penggunaan patch tool, efek blur, atau karakteristik rendering digital."
            # Skor pinalti berbasis kebersihan piksel
            noise_auth_score = max(10.0, round(mean_variance * 35, 2))
        
        # Pengecekan B: Uji ketidakrataan sebaran blok partikel citra
        else:
            if variance_std > (mean_variance * static_threshold_multiplier):
                warnings.append("Pola sebaran partikel gambar tidak rata dan perlu dibandingkan dengan ELA, metadata, serta skor AI.")
                interpretation = "Ditemukan variasi noise lokal pada beberapa area gambar. Indikasi ini bersifat pendukung dan belum cukup untuk menyimpulkan splicing tanpa korelasi dengan ELA, metadata, dan deteksi AI."
                researcher_note = "Varians lokal antar-blok melewati ambang batas deviasi standar statis citra; gunakan sebagai sinyal pendukung, bukan vonis tunggal."
                
                # Menghitung pinalti skor murni dari rasio lonjakan deviasi
                deviation_ratio = variance_std / (mean_variance * static_threshold_multiplier)
                noise_auth_score = max(20.0, round(100.0 - (deviation_ratio * 20), 2))
            else:
                interpretation = "Kualitas sebaran partikel gambar dan tingkat eror kompresi piksel tersebar secara merata dan homogen di seluruh area dokumen."
                noise_auth_score = 100.0

        return {
            'status': 'success',
            'noise_map': noise_map,
            'metrics': {
                'channel_noise_variance': {
                    'red': round(variance_per_channel[0], 6) if len(variance_per_channel) > 0 else 0,
                    'green': round(variance_per_channel[1], 6) if len(variance_per_channel) > 1 else 0,
                    'blue': round(variance_per_channel[2], 6) if len(variance_per_channel) > 2 else 0
                },
                'overall_variance': round(float(mean_variance), 6),
                'block_variance_std': round(variance_std, 6),
                'blocks_analyzed': len(block_variances),
                'noise_authenticity_score': noise_auth_score
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
