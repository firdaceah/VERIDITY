import sys
import json
import os
from datetime import datetime
from PIL import Image 

# Import fungsi utama dari folder analysis/
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
        ela_anomaly_score = ela_metrics['anomaly_score']
        
        if ela_img.mode != 'RGB':
            ela_img = ela_img.convert('RGB')

        ela_filename = f"ela_{time_suffix}.jpg"
        ela_path = os.path.join(output_dir, ela_filename)
        ela_img.save(ela_path, "JPEG")

        # --- 3. DEEPFAKE/AI DETECTION (Dinaikkan ke atas demi cross-reference) ---
        ai_results = detect_gan_fingerprint(image_path)
        gan_score = ai_results['metrics']['gan_score']
        is_deepfake_positive = gan_score > 0.5

        # --- 4. NOISE ANALYSIS (Cross-reference Aktif) ---
        noise_results = generate_noise_map(
            image_path, 
            sigma=2.0, 
            ela_anomaly_score=ela_anomaly_score, 
            is_deepfake_positive=is_deepfake_positive
        )
        noise_filename = f"noise_{time_suffix}.png"
        
        if 'noise_map' in noise_results:
            noise_img = noise_results['noise_map']
            if not isinstance(noise_img, Image.Image):
                import cv2
                noise_img = Image.fromarray(cv2.cvtColor(noise_img, cv2.COLOR_BGR2RGB))
            
            noise_path = os.path.join(output_dir, noise_filename)
            noise_img.save(noise_path, "PNG")

        # --- 5. LOGIKA KESIMPULAN VERDICT AKHIR ---
        ela_auth_score = max(0, 100 - (ela_anomaly_score * 3))
        ai_auth_score = 100 - (gan_score * 100)
        meta_auth_score = meta_report['summary']['authenticity_score']

        # Hitung skor total awal dengan bobot
        final_score = (ela_auth_score * 0.4) + (meta_auth_score * 0.4) + (ai_auth_score * 0.2)
        
        # Penentuan Hierarki Verdict Murni
        verdict = "AUTHENTIC"
        
        if is_deepfake_positive:
            verdict = "DEEPFAKE / AI GENERATED"
            final_score = min(final_score, 30.0) # Paksa drop skor ke zona bahaya
        elif final_score < 60 or meta_report['summary']['verdict'] == "REKAYASA DIGITAL / EDITING" or ela_anomaly_score > 30:
            verdict = "MANIPULATED"

        # Sinkronisasi status string sub-report sub-fungsi
        if verdict == "DEEPFAKE / AI GENERATED":
            meta_report['summary']['verdict'] = "REKAYASA DIGITAL / GENERATOR AI (SANGAT BERBAHAYA)"
        elif verdict == "MANIPULATED":
            meta_report['summary']['verdict'] = "REKAYASA DIGITAL / EDITING"
        else:
            meta_report['summary']['verdict'] = "KAMERA FISIK REAL (OTENTIK)"

        # Kompilasi ke struktur JSON Master Report
        full_report = {
            "status": "success",
            "verdict": verdict,
            "final_score": round(final_score, 2),
            "timestamp": datetime.now().isoformat(),
            "results": {
                "metadata": meta_report,
                "ela": {
                    "interpretation": report_forensic['interpretation'],
                    "image_url": ela_filename,
                    "metrics": ela_metrics
                },
                "noise": {
                    "interpretation": noise_results.get('interpretation', 'Normal'),
                    "warnings": noise_results.get('warnings', []),
                    "image_url": noise_filename,
                    "researcher_note": noise_results.get('researcher_note')
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