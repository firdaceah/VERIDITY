import sys
import json
import os
from datetime import datetime
# Import library tambahan untuk manipulasi gambar jika diperlukan
from PIL import Image 

# Import semua fungsi utama dari folder analysis/
from analysis.ela import forensic_analysis
from analysis.metadata_analysis import full_metadata_analysis
from analysis.noise_map import generate_noise_map
from analysis.deepfake_detector import detect_gan_fingerprint

def run_full_investigation(image_path, output_dir):
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)

    try:
        
        time_suffix = datetime.now().strftime("%Y%m%d_%H%M%S")
        # --- 1. METADATA ANALYSIS ---
        meta_report = full_metadata_analysis(image_path)

        # --- 2. ELA ANALYSIS ---
        report_forensic = forensic_analysis(image_path)
        ela_img = report_forensic['ela_90']
        ela_metrics = report_forensic['ela_90_metrics']
        
        if ela_img.mode != 'RGB':
            ela_img = ela_img.convert('RGB')

        ela_filename = f"ela_{time_suffix}.jpg"
        ela_path = os.path.join(output_dir, ela_filename)
        ela_img.save(ela_path, "JPEG")

        # --- 3. NOISE ANALYSIS (DIPERBAIKI) ---
        noise_results = generate_noise_map(image_path)
        noise_filename = f"noise_{time_suffix}.png"
        
        # Simpan visual Noise Map agar bisa muncul di Laravel
        if 'noise_map' in noise_results:
            noise_img = noise_results['noise_map']
            if not isinstance(noise_img, Image.Image):
                import cv2
                noise_img = Image.fromarray(cv2.cvtColor(noise_img, cv2.COLOR_BGR2RGB))
            
            noise_path = os.path.join(output_dir, noise_filename)
            noise_img.save(noise_path, "PNG")

        # --- 4. DEEPFAKE/AI DETECTION ---
        ai_results = detect_gan_fingerprint(image_path)

        # --- 5. LOGIKA KESIMPULAN (REVISI TOTAL) ---
        # Mengubah skor ELA menjadi nilai otentikasi (100 - (skor * sensitivitas))
        ela_auth_score = max(0, 100 - (ela_metrics['anomaly_score'] * 3))
        
        # Skor AI (100 - persentase GAN)
        ai_auth_score = 100 - (ai_results['metrics']['gan_score'] * 100)
        
        # Skor Metadata
        meta_auth_score = meta_report['summary']['authenticity_score']

        # HITUNG FINAL SCORE DENGAN BOBOT: ELA(40%), Metadata(40%), AI(20%)
        final_score = (ela_auth_score * 0.4) + (meta_auth_score * 0.4) + (ai_auth_score * 0.2)
        
        # Tentukan Verdict
        verdict = "AUTHENTIC"
        # Jika salah satu layer sangat buruk, langsung beri verdict MANIPULATED
        if final_score < 60 or meta_report['summary']['verdict'] == "LIKELY MANIPULATED" or ela_metrics['anomaly_score'] > 30:
            verdict = "MANIPULATED"

        # Gabungkan semua jadi satu JSON besar untuk Laravel
        full_report = {
            "status": "success",
            "verdict": verdict,
            "final_score": round(final_score, 2),
            "timestamp": datetime.now().isoformat(),
            "results": {
                "metadata": meta_report,
                "ela": {
                    "image_url": ela_filename,
                    "metrics": ela_metrics
                },
                "noise": {
                    "interpretation": noise_results.get('interpretation', 'Normal'),
                    "warnings": noise_results.get('warnings', []),
                    "image_url": noise_filename
                },
                "ai_detection": ai_results
            }
        }

        print(json.dumps(full_report))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"status": "error", "message": "Missing arguments"}))
    else:
        img = sys.argv[1]
        out = sys.argv[2]
        run_full_investigation(img, out)