import os
import sys
import json
from io import BytesIO

# Memperbaiki jalur import internal agar mengarah ke folder analysis milikmu
from analysis.document_pdf_utils import extract_any_document
from analysis.document_detector_core import classify_text_hf

def run_document_analysis(file_bytes, file_extension):
    """
    Fungsi utama untuk pipeline analisis forensik dokumen teks Veridity.
    Menerima binary bytes file dan string ekstensi berkas.
    """
    try:
        # 1. Ekstraksi teks otomatis berdasarkan format berkas (PDF/DOCX) via Bytes stream
        raw_text = extract_any_document(file_bytes, file_extension)
        
        # Validasi batas minimal teks dokumen agar model NLP presisi
        if not raw_text or len(raw_text.split()) < 5:
            return {
                "status": "error",
                "message": "Dokumen kosong atau teks terlalu pendek untuk dianalisis forensik. Gunakan PDF dokumen teks, bukan PDF hasil ekspor PPT/slide atau scan gambar."
            }
            
        # 2. Eksekusi analisis teks lewat model Hugging Face
        # classify_text_hf mengembalikan: (classification_map, percentages)
        classification_map, percentages = classify_text_hf(raw_text)
        
        # 3. Hitung Skor Akhir Keaslian Berdasarkan Kontribusi Kalimat Manusia
        # Nilai total keaslian bergerak searah dengan persentase 'Human-written'
        human_p = percentages.get("Human-written", 0.0)
        ai_p = percentages.get("AI-generated", 0.0)
        hybrid_p = percentages.get("AI-generated & AI-refined", 0.0) + percentages.get("Human-written & AI-refined", 0.0)
        
        final_score = human_p
        
        # 4. Tentukan Klasifikasi Vonis dan Deskripsi Hasil Eksperimen untuk Matriks Sidang
        if final_score >= 80.0:
            summary_label = "AUTHENTIC (HUMAN WRITTEN)"
            summary_color = "success"
            interpretation = "Gaya bahasa memiliki variasi panjang kalimat yang sangat dinamis dengan kekayaan diksi yang alami khas tulisan manusia murni."
        elif final_score >= 60.0:
            summary_label = "MIXED TEXT (AI ASSISTED)"
            summary_color = "warning"
            interpretation = "Terdeteksi kombinasi gaya bahasa campuran. Sebagian paragraf terindikasi disusun manual dan sebagian lainnya disisipi kalimat bentukan AI."
        else:
            summary_label = "MAYORITAS AI GENERATED"
            summary_color = "danger"
            interpretation = "Mayoritas kalimat terindikasi kuat dibuat AI. Jika masih ada bagian yang terdeteksi Human-written, bagian tersebut tetap dihitung sebagai porsi tulisan manusia dan dapat dilihat pada arsiran serta rincian NLP metrics."

        return {
            "status": "success",
            "final_score": round(final_score, 2),
            "summary_label": summary_label,
            "summary_color": summary_color,
            # SERTAKAN VARIABEL INI AGAR LARAVEL BISA MENYIMPANNYA
            "classification_map": classification_map, 
            "results": {
                "document": {
                    "verdict": summary_label,
                    "text_authenticity_score": round(final_score, 2),
                    "interpretation": interpretation,
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
            "message": f"Terjadi kegagalan sistem pada modul forensik dokumen: {str(e)}"
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
