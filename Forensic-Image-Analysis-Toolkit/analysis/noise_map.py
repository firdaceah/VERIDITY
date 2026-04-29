from PIL import Image, ImageFilter, ImageChops
from pathlib import Path
import numpy as np
import os

def generate_noise_map(image_path, sigma=2.0):
    """
    Menghasilkan noise map dengan mendeteksi komponen frekuensi tinggi.
    Berguna untuk mendeteksi retouching, splicing, dan cloning.
    
    Telah dioptimasi untuk mengurangi false positive pada tekstur alami.
    """
    try:
        img = Image.open(image_path).convert('RGB')

        # 1. Menyiapkan direktori output (menggunakan path relatif agar aman)
        temp_dir = Path(image_path).parent # Menyimpan di folder yang sama dengan output_dir
        
        # 2. Proses pemisahan channel
        channels = img.split()
        noise_maps = []
        variance_per_channel = []

        for channel in channels:
            # Terapkan Gaussian Blur untuk menghaluskan gambar
            blurred = channel.filter(ImageFilter.GaussianBlur(radius=sigma))

            # Hitung perbedaan (High-pass filter effect)
            # Ini akan mengambil sisa 'noise' atau detail tajam
            diff = ImageChops.difference(channel, blurred)

            # Perkuat kontras untuk menonjolkan noise agar terlihat secara visual
            enhanced = ImageChops.multiply(diff, diff)
            noise_maps.append(enhanced)

            # Hitung varians (tingkat noise) pada channel ini
            noise_array = np.array(diff)
            variance_per_channel.append(float(np.var(noise_array)))

        # Gabungkan kembali channel menjadi gambar RGB Noise Map
        noise_map = Image.merge('RGB', noise_maps)

        # 3. Analisis Konsistensi Noise (Metode Block-based)
        noise_array_full = np.array(noise_map)
        h, w = noise_array_full.shape[:2]
        block_size = 64
        block_variances = []

        # Membagi gambar menjadi blok 64x64 untuk mencari area yang 'berbeda sendiri'
        for i in range(0, h - block_size, block_size):
            for j in range(0, w - block_size, block_size):
                block = noise_array_full[i:i+block_size, j:j+block_size]
                block_variances.append(np.var(block))

        # Statistik Noise
        mean_variance = np.mean(block_variances)
        variance_std = float(np.std(block_variances))

       # 4. Logika Penentuan Warnings (Optimasi Threshold)
        warnings = []
        
        # Pengecekan 1: Jika gambar terlalu mulus (Potensi AI/Smoothing)
        if mean_variance < 2.0:
            warnings.append("Very low noise levels - possible heavy smoothing/retouching")
            
        # Pengecekan 2: Konsistensi (Hanya jika noise dasar cukup tinggi)
        # Jika mean_variance tinggi, kita pakai threshold yang lebih longgar (1.5)
        # agar tidak baper sama tekstur daun atau tembok.
        if mean_variance > 10.0:
            if variance_std > mean_variance * 1.5: 
                warnings.append("High noise inconsistency detected - possible manipulation")
        else:
            # Jika noise standar (low-mid), kita pakai threshold 1.2
            if variance_std > mean_variance * 1.2:
                warnings.append("High noise inconsistency detected - possible manipulation")

        # 5. Penentuan Interpretasi Final
        if not warnings:
            interpretation = "Noise levels appear uniform and consistent with authentic images."
        else:
            interpretation = "Inconsistent noise patterns detected. This may indicate local editing or splicing."

        return {
            'status': 'success',
            'noise_map': noise_map, # Mengembalikan objek Image untuk diproses run_full_investigation.py
            'metrics': {
                'channel_noise_variance': {
                    'red': round(variance_per_channel[0], 4),
                    'green': round(variance_per_channel[1], 4),
                    'blue': round(variance_per_channel[2], 4)
                },
                'overall_variance': round(float(mean_variance), 4),
                'block_variance_std': round(variance_std, 4),
                'blocks_analyzed': len(block_variances)
            },
            'warnings': warnings,
            'interpretation': interpretation
        }

    except Exception as e:
        return {
            'status': 'error',
            'error': str(e),
            'noise_map': None
        }