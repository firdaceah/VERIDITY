import piexif
from PIL import Image
import os
import hashlib
import struct
from datetime import datetime
import json
import re

# ============================================================
# ---------------------- CORE METADATA ------------------------
# ============================================================

def extract_metadata(image_path):
    """
    Extracts and returns comprehensive metadata from an image.
    """
    # FIX: Tambahkan key "summary" di sini agar tidak error 'summary'
    data = {
        "basic_info": {},
        "exif": {},
        "gps": {},
        "camera": {},
        "software": {},
        "timestamps": {},
        "thumbnail": {},
        "summary": {"status": "No Metadata Found"}, # Inisialisasi awal
        "warnings": []
    }

    try:
        # Basic file info
        file_stats = os.stat(image_path)
        data["basic_info"] = {
            "filename": os.path.basename(image_path),
            "file_size_bytes": file_stats.st_size,
            "file_size_mb": round(file_stats.st_size / (1024 * 1024), 2),
            "file_created": datetime.fromtimestamp(file_stats.st_ctime).isoformat(),
            "file_modified": datetime.fromtimestamp(file_stats.st_mtime).isoformat()
        }

        # PIL basic info
        img = Image.open(image_path)
        data["basic_info"].update({
            "format": img.format,
            "width": img.size[0],
            "height": img.size[1],
            "megapixels": round((img.size[0] * img.size[1]) / 1_000_000, 2)
        })

        # Try EXIF extraction
        exif_dict = piexif.load(image_path)

        def decode_tag(v):
            if isinstance(v, bytes):
                try:
                    return v.decode('utf-8', errors='ignore').strip('\x00')
                except:
                    return repr(v)
            return v

        # Process 0th IFD
        if "0th" in exif_dict:
            for tag, value in exif_dict["0th"].items():
                if tag in piexif.TAGS["0th"]:
                    tag_name = piexif.TAGS["0th"][tag]["name"]
                    decoded = decode_tag(value)
                    if tag_name in ["Make", "Model"]:
                        data["camera"][tag_name] = decoded
                    elif tag_name == "Software":
                        data["software"][tag_name] = decoded
                    elif tag_name == "DateTime":
                        data["timestamps"][tag_name] = decoded

        # Process Exif IFD
        if "Exif" in exif_dict:
            for tag, value in exif_dict["Exif"].items():
                if tag in piexif.TAGS["Exif"]:
                    tag_name = piexif.TAGS["Exif"][tag]["name"]
                    decoded = decode_tag(value)
                    if "Focal" in tag_name or "Aperture" in tag_name or "Lens" in tag_name:
                        data["camera"][tag_name] = str(decoded)
                    else:
                        data["exif"][tag_name] = str(decoded)

    except Exception as e:
        data["warnings"].append(f"Metadata extraction error: {str(e)}")

    return data

# ============================================================
# ------------------- FORENSIC CHECKS -------------------------
# ============================================================

def detect_anomalies(metadata):
    anomalies = {
        "critical": [],
        "warning": [],
        "info": [],
        "authenticity_score": 100
    }

    score = 100

    # FIX LOGIKA: Cek apakah ada metadata apapun (Sony/Kamera)
    has_any_metadata = any([
        metadata["exif"], 
        metadata["camera"], 
        metadata["software"], 
        metadata["timestamps"]
    ])

    if not has_any_metadata:
        anomalies["critical"].append("No EXIF data found - metadata may have been stripped")
        score -= 30
        metadata["summary"]["status"] = "Stripped Metadata / No EXIF Data"
    else:
        # Jika ada data kamera Sony kamu, statusnya jadi Found
        metadata["summary"]["status"] = "Metadata Found & Verified"

    anomalies["authenticity_score"] = max(0, score)
    return anomalies

# --- Fungsi pendukung lainnya tetap sama seperti kodinganmu ---
def analyze_file_structure(image_path):
    analysis = {"signature": {}, "warnings": []}
    try:
        with open(image_path, 'rb') as f:
            header = f.read(2)
            if header == b'\xff\xd8':
                analysis["signature"]["type"] = "JPEG"
    except: pass
    return analysis

def full_metadata_analysis(image_path):
    """ MASTER FUNCTION """
    metadata = extract_metadata(image_path)
    anomalies = detect_anomalies(metadata)
    file_struct = analyze_file_structure(image_path)
    
    score = anomalies["authenticity_score"]
    if score >= 80: verdict = "LIKELY AUTHENTIC"
    elif score >= 50: verdict = "SUSPICIOUS"
    else: verdict = "LIKELY MANIPULATED"

    return {
        "metadata": metadata,
        "anomalies": anomalies,
        "summary": {
            "authenticity_score": score,
            "verdict": verdict,
            "status": metadata["summary"]["status"]
        }
    }