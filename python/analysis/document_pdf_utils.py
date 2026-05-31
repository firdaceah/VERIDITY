import fitz
from io import BytesIO
from datetime import datetime
import nltk
from nltk.tokenize import word_tokenize
from docx import Document  
import os

nltk.download('punkt', quiet=True)

def extract_text_from_pdf(pdf_bytes):
    """Extract text from all pages of a PDF."""
    doc = fitz.open(stream=pdf_bytes, filetype="pdf")
    all_text = ""
    for page in doc:
        all_text += page.get_text("text") + "\n"
    doc.close()
    return all_text

def extract_text_from_docx(docx_bytes):
    """Extract text from a Word Document (.docx) using bytes stream."""
    doc = Document(BytesIO(docx_bytes))
    all_text = ""
    for para in doc.paragraphs:
        if para.text.strip():
            all_text += para.text + "\n"
    return all_text

def extract_any_document(file_bytes, file_extension):
    """Jembatan otomatis untuk mengekstrak teks berdasarkan ekstensi berkas."""
    ext = file_extension.lower().get_text if hasattr(file_extension, 'get_text') else str(file_extension).lower()
    ext = ext.lstrip('.')
    
    if ext == 'pdf':
        return extract_text_from_pdf(file_bytes)
    elif ext == 'docx':
        return extract_text_from_docx(file_bytes)
    else:
        raise ValueError(f"Format dokumen '{ext}' tidak didukung oleh sistem Veridity!")

def word_count(text):
    return len(word_tokenize(text))

def generate_annotated_pdf(pdf_bytes, classification_map, metadata_summary="MIXED TEXT (AI ASSISTED)", audit_id="61", analyzed_at=None):
    """
    Menghasilkan laporan PDF formal dengan menyisipkan Kop Surat Veridity PENS 
    di halaman pertama, lalu memberikan warna stabilo AI pada teks di halaman berikutnya.
    """
    doc = fitz.open(stream=pdf_bytes, filetype="pdf")
    kop_page = doc.new_page(pno=0, width=595, height=842) 
    
    # Desain Garis Pembatas Kop Surat
    kop_page.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=(30/255, 58/255, 138/255), width=2)
    
    # Tulis Text Kop Surat - Menggunakan fontname standar langsung tanpa init_font
    kop_page.insert_text(fitz.Point(50, 65), "VeriDity.", fontsize=22, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    kop_page.insert_text(fitz.Point(340, 65), "LAPORAN INTEGRITAS DIGITAL DOKUMEN", fontsize=11, fontname="helvetica-bold", color=(100/255, 116/255, 139/255))
    
    # Judul Seksi Informasi Master
    kop_page.draw_rect(fitz.Rect(50, 110, 545, 130), color=None, fill=(248/255, 250/255, 252/255))
    kop_page.insert_text(fitz.Point(55, 124), "INFORMASI DOKUMEN BARANG BUKTI DOKUMEN", fontsize=10, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    
    if not analyzed_at:
        analyzed_at = datetime.now().strftime("%d %b %Y, %H:%M") + " WIB"
        
    kop_page.insert_text(fitz.Point(55, 160), f"Kode Investigasi    :  #VRD-{audit_id}", fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 185), f"Waktu Analisis       :  {analyzed_at}", fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 210), f"Format Objek        :  PDF (Rumpun Linguistic Teks)", fontsize=11, fontname="helvetica")
    
    kop_page.insert_text(fitz.Point(55, 238), f"Vonis Akhir Berkas : ", fontsize=11, fontname="helvetica")
    
    # Sinkronisasi Warna Badge Status
    status_upper = str(metadata_summary).upper()
    badge_color = (22/255, 163/255, 74/255) # Default: Hijau
    
    if "AI" in status_upper or "MIXED" in status_upper:
        badge_color = (234/255, 88/255, 12/255) # Orange
        
    if "FULL" in status_upper or "FORGED" in status_upper:
        badge_color = (220/255, 38/255, 38/255) # Red

    kop_page.draw_rect(fitz.Rect(175, 224, 380, 246), color=badge_color, fill=badge_color, radius=None)
    kop_page.insert_text(fitz.Point(185, 240), metadata_summary, fontsize=10, fontname="helvetica-bold", color=(1,1,1))

    # Teks Legenda Panduan Warna Stabilo
    kop_page.draw_rect(fitz.Rect(50, 280, 545, 300), color=None, fill=(248/255, 250/255, 252/255))
    kop_page.insert_text(fitz.Point(55, 294), "PANDUAN WARNA ANOTASI SEBARAN KALIMAT (AI METRICS)", fontsize=10, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    
    kop_page.insert_text(fitz.Point(55, 330), "• Merah Muda (Pink)       :  Terindikasi Kuat Buatan Mesin Generatif (AI-Generated)", fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 355), "• Jingga (Orange)            :  Hasil Parafrase / Refinement Mesin Komputer (AI-Refined)", fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 380), "• Biru Muda (Light Blue)  :  Teks Gabungan / Hasil Editan Manusia & AI", fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 405), "• Tanpa Warna Stabilo     :  Murni Gaya Tulisan Alami Manusia (Human-written)", fontsize=11, fontname="helvetica")

    # Catatan Kaki Validasi Kampus PENS
    kop_page.draw_line(fitz.Point(50, 780), fitz.Point(545, 780), color=(226/255, 232/255, 240/255), width=1)
    kop_page.insert_text(fitz.Point(50, 795), "Dokumen ini diterbitkan oleh Veridity Platform Forensik. Dicetak otomatis via Python Engine Gateway.", fontsize=8, fontname="helvetica", color=(148/255, 163/255, 184/255))
    kop_page.insert_text(fitz.Point(50, 808), "Politeknik Elektronika Negeri Surabaya (PENS) - Jurusan Teknik Informatika", fontsize=8, fontname="helvetica-bold", color=(148/255, 163/255, 184/255))

    # Logika Penyorotan Kalimat
    def hex_to_rgb_float(hex_color):
        hex_color = hex_color.lstrip('#')
        return (int(hex_color[0:2], 16) / 255.0, int(hex_color[2:4], 16) / 255.0, int(hex_color[4:6], 16) / 255.0)

    COLOR_MAPPING = {
        "AI-generated": "#ffcccc",
        "AI-generated & AI-refined": "#ffe5cc",
        "Human-written & AI-refined": "#e6f2ff"
    }

    if not classification_map or not isinstance(classification_map, dict):
        classification_map = {}

    for sentence, label in classification_map.items():
        if label == "Human-written":
            continue
            
        color_hex = COLOR_MAPPING.get(label)
        if not color_hex:
            continue
        color = hex_to_rgb_float(color_hex)
        
        # 1. Bersihkan kalimat dari spasi ganda dan newline tersembunyi
        cleaned_sentence = " ".join(sentence.split()).strip()
        if len(cleaned_sentence) < 10:
            continue
            
        # 2. Potong kalimat panjang menjadi fragmen kecil berisi 4 kata (Strategi Agresif)
        words = cleaned_sentence.split()
        chunk_size = 4
        
        for i in range(0, len(words), chunk_size):
            chunk = " ".join(words[i:i+chunk_size])
            
            if len(chunk.strip()) < 8:
                continue
                
            # 3. Cari dan beri warna stabilo di halaman dokumen asli (skip halaman 0 kop surat)
            for page_num in range(1, len(doc)):
                page = doc[page_num]
                rects = page.search_for(chunk)
                
                for rect in rects:
                    annot = page.add_highlight_annot(rect)
                    annot.set_colors(stroke=color)
                    annot.update()

    out_bytes = doc.write()
    doc.close()
    return BytesIO(out_bytes)