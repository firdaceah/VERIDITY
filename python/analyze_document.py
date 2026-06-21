import os
import sys
import json
from io import BytesIO

# Memperbaiki jalur import internal agar mengarah ke folder analysis milikmu
from analysis.document_pdf_utils import extract_any_document
from analysis.document_detector_core import analyze_text_lightweight_v2

MESSAGES = {
    "cancelled": {
        "en": "Analysis was cancelled.",
        "id": "Analisis dibatalkan.",
    },
    "empty_document": {
        "en": "This document does not contain enough readable text for reliable analysis. Please upload a text-based PDF with more content.",
        "id": "Dokumen ini tidak memiliki teks terbaca yang cukup untuk dianalisis dengan andal. Silakan unggah PDF berbasis teks dengan isi yang lebih lengkap.",
    },
    "document_insufficient_text": {
        "en": "This document does not contain enough readable text for reliable analysis. Please upload a text-based PDF with more content.",
        "id": "Dokumen ini tidak memiliki teks terbaca yang cukup untuk dianalisis dengan andal. Silakan unggah PDF berbasis teks dengan isi yang lebih lengkap.",
    },
    "system_failure": {
        "en": "Document analysis is temporarily unavailable. Please try again later.",
        "id": "Analisis dokumen sementara tidak tersedia. Silakan coba lagi nanti.",
    },
}

LABELS = {
    "Likely Human": {
        "en": "Likely Human",
        "id": "Kemungkinan Ditulis Manusia",
        "key": "document_likely_human",
        "interpretation": {
            "en": "The document shows varied sentence rhythm, natural wording, and limited signs of overly uniform AI-style structure.",
            "id": "Dokumen menunjukkan variasi ritme kalimat, pilihan kata yang natural, dan sedikit tanda struktur seragam khas tulisan AI.",
        },
        "interpretation_key": "document_likely_human_style",
    },
    "Mixed Indicators": {
        "en": "Mixed Indicators",
        "id": "Indikator Campuran",
        "key": "document_mixed_indicators",
        "interpretation": {
            "en": "The document contains a mix of natural writing patterns and structured or repetitive indicators that may suggest AI assistance.",
            "id": "Dokumen memiliki campuran pola tulisan natural dan indikator struktur atau repetisi yang dapat mengarah ke bantuan AI.",
        },
        "interpretation_key": "document_mixed_indicators_style",
    },
    "Likely AI-Written": {
        "en": "Likely AI-Written",
        "id": "Kemungkinan Ditulis AI",
        "key": "document_likely_ai_written",
        "interpretation": {
            "en": "The document contains repeated, uniform, or highly structured linguistic patterns often associated with AI-written text.",
            "id": "Dokumen memiliki pola bahasa yang repetitif, seragam, atau terlalu terstruktur yang sering berkaitan dengan teks buatan AI.",
        },
        "interpretation_key": "document_likely_ai_written_style",
    },
}


def normalize_language(language):
    return "id" if str(language).lower() == "id" else "en"


def message(key, language):
    locale = normalize_language(language)
    return MESSAGES[key].get(locale, MESSAGES[key]["en"])


def _cancelled(language):
    return {
        "status": "cancelled",
        "message": message("cancelled", language),
    }


def _insufficient_text(language):
    return {
        "status": "insufficient_text",
        "message_key": "document_insufficient_text",
        "message": message("document_insufficient_text", language),
    }


def _localized_label(label, language):
    config = LABELS.get(label, LABELS["Mixed Indicators"])
    return config.get(language, config["en"]), config


def run_document_analysis(file_bytes, file_extension, language="en", is_cancelled=None):
    """
    Fungsi utama untuk pipeline analisis forensik dokumen teks Veridity.
    Menerima binary bytes file dan string ekstensi berkas.
    """
    language = normalize_language(language)
    is_cancelled = is_cancelled or (lambda: False)

    try:
        if is_cancelled():
            return _cancelled(language)

        # 1. Ekstraksi teks otomatis berdasarkan format berkas (PDF/DOCX) via Bytes stream
        raw_text = extract_any_document(file_bytes, file_extension)

        if is_cancelled():
            return _cancelled(language)
        
        if not raw_text or len(raw_text.split()) < 5:
            return _insufficient_text(language)
            
        # 2. Eksekusi analisis teks ringan berbasis pola linguistik.
        detection = analyze_text_lightweight_v2(raw_text)

        if detection.get("status") == "insufficient_text":
            return {
                **_insufficient_text(language),
                "metrics": detection.get("metrics", {}),
                "engine": detection.get("engine", "lightweight_v2"),
            }

        classification_map = detection["classification_map"]
        percentages = detection["percentages"]

        if is_cancelled():
            return _cancelled(language)
        
        # 3. Hitung Skor Akhir dari engine lightweight_v2.
        human_p = percentages.get("Human-written", 0.0)
        ai_p = percentages.get("AI-generated", 0.0)
        hybrid_p = percentages.get("AI-generated & AI-refined", 0.0) + percentages.get("Human-written & AI-refined", 0.0)
        
        final_score = detection.get("score", human_p)
        
        # 4. Tentukan label dokumen yang lebih hati-hati.
        summary_label, label_config = _localized_label(detection.get("label"), language)
        summary_key = label_config["key"]
        summary_color = detection.get("summary_color", "warning")
        interpretation = label_config["interpretation"].get(language, label_config["interpretation"]["en"])
        interpretation_key = label_config["interpretation_key"]

        return {
            "status": "success",
            "final_score": round(final_score, 2),
            "summary_label": summary_label,
            "summary_key": summary_key,
            "summary_color": summary_color,
            "engine": detection.get("engine", "lightweight_v2"),
            # SERTAKAN VARIABEL INI AGAR LARAVEL BISA MENYIMPANNYA
            "classification_map": classification_map, 
            "results": {
                "document": {
                    "engine": detection.get("engine", "lightweight_v2"),
                    "verdict": summary_label,
                    "verdict_key": summary_key,
                    "text_authenticity_score": round(final_score, 2),
                    "interpretation": interpretation,
                    "interpretation_key": interpretation_key,
                    "metrics": {
                        "human_p": round(human_p, 2),
                        "ai_p": round(ai_p, 2),
                        "hybrid_p": round(hybrid_p, 2),
                        **detection.get("metrics", {}),
                    }
                }
            }
        }

    except Exception as e:
        return {
            "status": "error",
            "message": message("system_failure", language)
        }

# Blok testing mandiri via CLI Terminal lokal
if __name__ == "__main__":
    # Ganti path ini dengan berkas testing lokal kamu jika ingin mencoba langsung di terminal
    test_file = "sample.docx"
    
    if os.path.exists(test_file):
        _, extension = os.path.splitext(test_file)
        with open(test_file, "rb") as f:
            binary_data = f.read()
            
        print(f"Menguji analisis dokumen: {test_file}...")
        result = run_document_analysis(binary_data, extension)
        print(json.dumps(result, indent=4))
    else:
        print("Driver siap digunakan untuk FastAPI.")
