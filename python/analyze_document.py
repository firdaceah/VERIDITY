import os
import sys
import json
from io import BytesIO

# Memperbaiki jalur import internal agar mengarah ke folder analysis milikmu
from analysis.document_pdf_utils import extract_any_document
from analysis.document_detector_core import classify_text_hf

MESSAGES = {
    "cancelled": {
        "en": "Analysis was cancelled.",
        "id": "Analisis dibatalkan.",
    },
    "empty_document": {
        "en": "The document is empty or too short for forensic analysis. Use a text-based PDF document, not a PDF exported from slides or scanned images.",
        "id": "Dokumen kosong atau teks terlalu pendek untuk dianalisis forensik. Gunakan PDF dokumen teks, bukan PDF hasil ekspor PPT/slide atau scan gambar.",
    },
    "system_failure": {
        "en": "A system failure occurred in the document forensic module: ",
        "id": "Terjadi kegagalan sistem pada modul forensik dokumen: ",
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
        
        # Validasi batas minimal teks dokumen agar model NLP presisi
        if not raw_text or len(raw_text.split()) < 5:
            return {
                "status": "error",
                "message": message("empty_document", language)
            }
            
        # 2. Eksekusi analisis teks lewat model Hugging Face
        # classify_text_hf mengembalikan: (classification_map, percentages)
        classification_map, percentages = classify_text_hf(raw_text)

        if is_cancelled():
            return _cancelled(language)
        
        # 3. Hitung Skor Akhir Keaslian Berdasarkan Kontribusi Kalimat Manusia
        # Nilai total keaslian bergerak searah dengan persentase 'Human-written'
        human_p = percentages.get("Human-written", 0.0)
        ai_p = percentages.get("AI-generated", 0.0)
        hybrid_p = percentages.get("AI-generated & AI-refined", 0.0) + percentages.get("Human-written & AI-refined", 0.0)
        
        final_score = human_p
        
        # 4. Tentukan Klasifikasi Vonis dan Deskripsi Hasil Eksperimen untuk Matriks Sidang
        if final_score >= 80.0:
            summary_label = "AUTHENTIC (HUMAN WRITTEN)" if language == "en" else "OTENTIK (DITULIS MANUSIA)"
            summary_key = "document_authentic_human"
            summary_color = "success"
            interpretation = "The language style has dynamic sentence-length variation and natural word choice typical of human writing." if language == "en" else "Gaya bahasa memiliki variasi panjang kalimat yang sangat dinamis dengan kekayaan diksi yang alami khas tulisan manusia murni."
            interpretation_key = "document_human_style"
        elif final_score >= 60.0:
            summary_label = "MIXED TEXT (AI ASSISTED)" if language == "en" else "TEKS CAMPURAN (DIBANTU AI)"
            summary_key = "document_mixed_ai_assisted"
            summary_color = "warning"
            interpretation = "Mixed language patterns were detected. Some paragraphs appear manually written while others contain AI-assisted sentences." if language == "en" else "Terdeteksi kombinasi gaya bahasa campuran. Sebagian paragraf terindikasi disusun manual dan sebagian lainnya disisipi kalimat bentukan AI."
            interpretation_key = "document_mixed_style"
        else:
            summary_label = "MOSTLY AI GENERATED" if language == "en" else "MAYORITAS AI GENERATED"
            summary_key = "document_mostly_ai"
            summary_color = "danger"
            interpretation = "Most sentences are strongly indicated as AI-generated. Any detected human-written portions are still counted and shown in the highlights and NLP metrics." if language == "en" else "Mayoritas kalimat terindikasi kuat dibuat AI. Jika masih ada bagian yang terdeteksi Human-written, bagian tersebut tetap dihitung sebagai porsi tulisan manusia dan dapat dilihat pada arsiran serta rincian NLP metrics."
            interpretation_key = "document_mostly_ai_style"

        return {
            "status": "success",
            "final_score": round(final_score, 2),
            "summary_label": summary_label,
            "summary_key": summary_key,
            "summary_color": summary_color,
            # SERTAKAN VARIABEL INI AGAR LARAVEL BISA MENYIMPANNYA
            "classification_map": classification_map, 
            "results": {
                "document": {
                    "verdict": summary_label,
                    "verdict_key": summary_key,
                    "text_authenticity_score": round(final_score, 2),
                    "interpretation": interpretation,
                    "interpretation_key": interpretation_key,
                    "metrics": {
                        "human_p": round(human_p, 2),
                        "ai_p": round(ai_p, 2),
                        "hybrid_p": round(hybrid_p, 2)
                    }
                }
            }
        }

    except Exception as e:
        return {
            "status": "error",
            "message": f"{message('system_failure', language)}{str(e)}"
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
