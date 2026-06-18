import numpy as np
from PIL import Image

def detect_deepfake_artifacts(image_path):
    try:
        img = Image.open(image_path).convert('RGB')
        img_array = np.array(img, dtype=np.float32)

        from scipy.fft import fft2, fftshift
        gray = np.mean(img_array, axis=2)
        fft_result = fft2(gray)
        magnitude = np.abs(fftshift(fft_result))

        h, w = magnitude.shape
        center_h, center_w = h // 2, w // 2

        high_freq_ring = magnitude[center_h-20:center_h+20, center_w-20:center_w+20]
        high_freq_variance = np.var(high_freq_ring)

        r, g, b = img_array[:, :, 0], img_array[:, :, 1], img_array[:, :, 2]
        rg_correlation = np.corrcoef(r.flatten(), g.flatten())[0, 1]
        rb_correlation = np.corrcoef(r.flatten(), b.flatten())[0, 1]
        gb_correlation = np.corrcoef(g.flatten(), b.flatten())[0, 1]
        avg_channel_correlation = np.mean([rg_correlation, rb_correlation, gb_correlation])

        from scipy.ndimage import sobel
        gradient_x = sobel(gray, axis=1)
        gradient_y = sobel(gray, axis=0)
        gradient_magnitude = np.sqrt(gradient_x**2 + gradient_y**2)
        edge_sharpness = np.std(gradient_magnitude)

        return {
            "status": "analisis_selesai",
            "interpretation": "Mengukur keaslian piksel area wajah untuk mendeteksi distorsi buatan kecerdasan buatan (AI).",
            "artifacts": {
                "frequency_anomaly_score": float(high_freq_variance),
                "channel_correlation_score": float(avg_channel_correlation),
                "edge_sharpness_score": float(edge_sharpness)
            }
        }
    except Exception as e:
        return {"error": str(e), "status": "analisis_gagal"}

def detect_gan_fingerprint(image_path):
    try:
        img = Image.open(image_path).convert('RGB')
        img_array = np.array(img, dtype=np.float32)

        from scipy.fft import fft2, fftshift
        gray = np.mean(img_array, axis=2)
        fft_result = fft2(gray)
        magnitude = np.abs(fftshift(fft_result))
        magnitude_log = np.log(magnitude + 1)

        h, w = magnitude.shape
        center_h, center_w = h // 2, w // 2

        y, x = np.ogrid[:h, :w]
        distance = np.sqrt((y - center_h)**2 + (x - center_w)**2)

        max_radius = min(center_h, center_w)
        radial_bins = 50
        radial_profile = []

        for i in range(radial_bins):
            r_inner = int(i * max_radius / radial_bins)
            r_outer = int((i + 1) * max_radius / radial_bins)
            mask = (distance >= r_inner) & (distance < r_outer)
            if np.any(mask):
                avg_magnitude = np.mean(magnitude_log[mask])
                radial_profile.append(float(avg_magnitude))

        radial_variance = float(np.var(radial_profile)) if radial_profile else 0
        peak_count = 0
        if len(radial_profile) > 3:
            for i in range(1, len(radial_profile) - 1):
                if radial_profile[i] > radial_profile[i-1] and radial_profile[i] > radial_profile[i+1]:
                    if radial_profile[i] > np.mean(radial_profile) + np.std(radial_profile):
                        peak_count += 1

        gan_score = 0
        gan_indicators = []

        if radial_variance > 1.5:
            gan_score += 0.3
            gan_indicators.append("Pola pencaran frekuensi piksel tidak natural")
        if peak_count > 5:
            gan_score += 0.4
            gan_indicators.append(f"Terdeteksi sidik jari spektral buatan komputer ({peak_count} titik)")

        quadrant_means = [
            np.mean(magnitude[:center_h, :center_w]),
            np.mean(magnitude[:center_h, center_w:]),
            np.mean(magnitude[center_h:, :center_w]),
            np.mean(magnitude[center_h:, center_w:])
        ]
        quadrant_symmetry = float(np.std(quadrant_means) / (np.mean(quadrant_means) + 1e-10))

        if quadrant_symmetry < 0.1:
            gan_score += 0.3
            gan_indicators.append("Simetri frekuensi terlalu presisi (Ciri khas buatan generator AI)")

        if gan_score > 0.7:
            likelihood = "SANGAT TINGGI"
            likelihood_key = "ai_likelihood_very_high"
        elif gan_score > 0.4:
            likelihood = "MENCURIGAKAN"
            likelihood_key = "ai_likelihood_suspicious"
        else:
            likelihood = "RENDAH / NEGATIF"
            likelihood_key = "ai_likelihood_low"

        # KALKULASI MANDIRI: Mengubah probabilitas GAN menjadi skor keaslian alami kamera fisik
        ai_auth_score = max(0.0, min(100.0, round(100.0 - (float(gan_score) * 100), 2)))

        return {
            "status": "success",
            "metrics": {
                "radial_frequency_variance": radial_variance,
                "spectral_peaks_detected": peak_count,
                "quadrant_symmetry": quadrant_symmetry,
                "gan_score": float(gan_score),
                "ai_authenticity_score": ai_auth_score # Suntikan metrik baru
            },
            "gan_indicators": gan_indicators,
            "gan_likelihood": likelihood,
            "gan_likelihood_key": likelihood_key,
            "interpretation": f"Tingkat indikasi foto wajah ini merupakan hasil rekayasa kecerdasan buatan (AI/Deepfake): {likelihood}.",
            "interpretation_key": "ai_deepfake_likelihood",
            "researcher_note": "Analisis menggunakan radially-averaged power spectrum untuk menangkap periodic artifacts sisa upsampling arsitektur GAN.",
            "researcher_note_key": "ai_radial_spectrum_note"
        }
    except Exception as e:
        return {"status": "error", "error": str(e)}
