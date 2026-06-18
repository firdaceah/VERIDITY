import piexif
from PIL import Image
import os
from datetime import datetime

def extract_metadata(image_path):
    data = {
        "basic_info": {},
        "exif": {},
        "gps": {},
        "camera": {},
        "software": {},
        "timestamps": {},
        "thumbnail": {},
        "summary": {"status": "Informasi Berkas Kosong"},
        "warnings": []
    }

    try:
        file_stats = os.stat(image_path)
        data["basic_info"] = {
            "filename": os.path.basename(image_path),
            "file_size_bytes": file_stats.st_size,
            "file_size_mb": round(file_stats.st_size / (1024 * 1024), 2),
            "file_created": datetime.fromtimestamp(file_stats.st_ctime).isoformat(),
            "file_modified": datetime.fromtimestamp(file_stats.st_mtime).isoformat()
        }

        img = Image.open(image_path)
        data["basic_info"].update({
            "format": img.format,
            "width": img.size[0],
            "height": img.size[1],
            "megapixels": round((img.size[0] * img.size[1]) / 1_000_000, 2)
        })

        exif_dict = piexif.load(image_path)

        def decode_tag(v):
            if isinstance(v, bytes):
                try:
                    return v.decode('utf-8', errors='ignore').strip('\x00')
                except:
                    return repr(v)
            return v

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
        data["warnings"].append(f"Gagal membaca rekam jejak metadata: {str(e)}")

    return data

def detect_anomalies(metadata):
    anomalies = {
        "critical": [],
        "warning": [],
        "info": [],
        "authenticity_score": 100
    }
    score = 100

    has_any_metadata = any([
        metadata["exif"], 
        metadata["camera"], 
        metadata["software"], 
        metadata["timestamps"]
    ])

    if not has_any_metadata:
        anomalies["critical"].append("Informasi kamera asli hilang - File gambar diproses ulang atau sengaja diekspor tanpa membawa riwayat kamera asli (Stripped)")
        score -= 30
        metadata["summary"]["status"] = "Metadata Kamera Hilang / Hasil Export"
    else:
        if metadata["software"]:
            software_used = ", ".join(metadata["software"].values())
            anomalies["warning"].append(f"Terdeteksi jejak modifikasi digital: Berkas pernah disimpan menggunakan aplikasi {software_used}")
            score -= 20
            metadata["summary"]["status"] = "Modifikasi via Aplikasi Editor"
        else:
            metadata["summary"]["status"] = "Metadata Kamera Asli Terverifikasi"

    anomalies["authenticity_score"] = max(0, score)
    return anomalies

def analyze_file_structure(image_path):
    analysis = {"signature": {}, "warnings": []}
    try:
        with open(image_path, 'rb') as f:
            header = f.read(2)
            if header == b'\xff\xd8':
                analysis["signature"]["type"] = "JPEG (Citra Digital)"
    except: 
        pass
    return analysis

def full_metadata_analysis(image_path):
    metadata = extract_metadata(image_path)
    anomalies = detect_anomalies(metadata)
    
    score = anomalies["authenticity_score"]
    
    if score >= 85: 
        verdict = "KAMERA FISIK REAL (OTENTIK)"
        verdict_key = "metadata_authentic_camera"
    elif score >= 60: 
        verdict = "TERINDIKASI EDITING (MENCURIGAKAN)"
        verdict_key = "metadata_suspicious_editing"
    else: 
        verdict = "REKAYASA DIGITAL / EDITING"
        verdict_key = "metadata_digital_editing"

    return {
        "metadata": metadata,
        "anomalies": anomalies,
        "summary": {
            "authenticity_score": score,
            "verdict": verdict,
            "verdict_key": verdict_key,
            "status": metadata["summary"]["status"]
        }
    }
