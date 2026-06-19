import sys
import json
import os
import contextlib
import io
from datetime import datetime
from PIL import Image 

# Import fungsi utama dari folder analysis/
from analysis.ela import forensic_analysis
from analysis.metadata_analysis import full_metadata_analysis
from analysis.noise_map import generate_noise_map
from analysis.deepfake_detector import detect_gan_fingerprint

MESSAGES = {
    "cancelled": {
        "en": "Analysis was cancelled.",
        "id": "Analisis dibatalkan.",
    },
}


def normalize_language(language):
    return "id" if str(language).lower() == "id" else "en"


def _cancelled(language):
    return {
        "status": "cancelled",
        "message": MESSAGES["cancelled"][normalize_language(language)],
    }


def run_full_investigation(image_path, output_dir, language="en", is_cancelled=None):
    language = normalize_language(language)
    is_cancelled = is_cancelled or (lambda: False)

    if not os.path.exists(output_dir):
        os.makedirs(output_dir)

    try:
        if is_cancelled():
            return _cancelled(language)

        time_suffix = datetime.now().strftime("%Y%m%d_%H%M%S")
        
        # --- 1. METADATA ANALYSIS ---
        meta_report = full_metadata_analysis(image_path)

        if is_cancelled():
            return _cancelled(language)

        # --- 2. ELA ANALYSIS ---
        report_forensic = forensic_analysis(image_path)
        ela_img = report_forensic['ela_90']
        ela_metrics = report_forensic['ela_90_metrics']

        if is_cancelled():
            return _cancelled(language)
        
        # Mengambil skor keaslian ELA murni dari sub-modul
        ela_auth_score = ela_metrics['ela_authenticity_score']
        ela_anomaly_score = ela_metrics['anomaly_score']
        
        if ela_img.mode != 'RGB':
            ela_img = ela_img.convert('RGB')

        ela_filename = f"ela_{time_suffix}.jpg"
        ela_path = os.path.join(output_dir, ela_filename)
        ela_img.save(ela_path, "JPEG")

        # --- 3. DEEPFAKE/AI DETECTION ---
        ai_results = detect_gan_fingerprint(image_path)
        gan_score = ai_results['metrics']['gan_score']
        is_deepfake_positive = gan_score > 0.5
        # Mengambil skor keaslian AI murni dari sub-modul
        ai_auth_score = ai_results['metrics']['ai_authenticity_score']

        if is_cancelled():
            return _cancelled(language)

        # --- 4. NOISE ANALYSIS ---
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

        # Mengambil skor keaslian Noise murni dari sub-modul
        noise_auth_score = noise_results.get('metrics', {}).get('noise_authenticity_score', 100.0)
        meta_auth_score = meta_report['summary']['authenticity_score']
        effective_meta_auth_score = meta_report['summary'].get('effective_authenticity_score', meta_auth_score)

        if is_cancelled():
            return _cancelled(language)

        # --- 5. RUMUS HARMONISASI BOBOT FORENSIK ---
        # ELA dan noise membaca anomali visual, sementara metadata dan AI
        # menjadi sinyal pendukung yang tetap punya porsi seimbang.
        final_score = (
            (ela_auth_score * 0.30)
            + (noise_auth_score * 0.30)
            + (effective_meta_auth_score * 0.20)
            + (ai_auth_score * 0.20)
        )

        if is_deepfake_positive:
            verdict = "DEEPFAKE / AI GENERATED"
        elif final_score < 65 or ela_anomaly_score > 30 or meta_report['summary']['verdict'] == "REKAYASA DIGITAL / EDITING":
            verdict = "MANIPULATED"
        else:
            verdict = "AUTHENTIC"

        full_report = {
            "status": "success",
            "verdict": verdict,
            "final_score": round(final_score, 2),
            "timestamp": datetime.now().isoformat(),
            "results": {
                "metadata": meta_report,
                "ela": {
                    "interpretation": report_forensic['interpretation'],
                    "interpretation_key": report_forensic.get('interpretation_key'),
                    "image_url": ela_filename,
                    "metrics": ela_metrics
                },
                "noise": {
                    "interpretation": noise_results.get('interpretation', 'Normal'),
                    "warnings": noise_results.get('warnings', []),
                    "warning_keys": noise_results.get('warning_keys', []),
                    "image_url": noise_filename,
                    "researcher_note": noise_results.get('researcher_note'),
                    "researcher_note_key": noise_results.get('researcher_note_key'),
                    "interpretation_key": noise_results.get('interpretation_key'),
                    "metrics": noise_results.get('metrics')
                },
                "ai_detection": ai_results
            }
        }

        return full_report

    except Exception as e:
        return {"status": "error", "message": str(e)}

def run_full_investigation_quiet(image_path, output_dir, language="en", is_cancelled=None):
    with contextlib.redirect_stdout(io.StringIO()):
        return run_full_investigation(image_path, output_dir, language=language, is_cancelled=is_cancelled)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"status": "error", "message": "Missing arguments"}))
    else:
        img = sys.argv[1]
        out = sys.argv[2]
        print(json.dumps(run_full_investigation(img, out)))
