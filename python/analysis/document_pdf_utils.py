import fitz
from io import BytesIO
from datetime import datetime
import nltk
from nltk.tokenize import word_tokenize
from docx import Document  
import os
import re
import unicodedata

for nltk_path in [
    *os.environ.get("NLTK_DATA", "").split(os.pathsep),
    os.path.join(os.environ.get("APPDATA", ""), "nltk_data"),
    os.path.join(os.environ.get("USERPROFILE", ""), "nltk_data"),
    os.path.join(os.getcwd(), "nltk_data"),
]:
    if nltk_path and os.path.isdir(nltk_path) and nltk_path not in nltk.data.path:
        nltk.data.path.append(nltk_path)

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
    try:
        return len(word_tokenize(text))
    except LookupError:
        return len(re.findall(r"\b\w+\b", text))

def _normalize_token(value):
    normalized = unicodedata.normalize("NFKD", str(value).lower())
    normalized = "".join(ch for ch in normalized if not unicodedata.combining(ch))
    return re.sub(r"[^a-z0-9]+", "", normalized)

def _page_tokens(page):
    tokens = []

    for item in page.get_text("words"):
        x0, y0, x1, y1, text, block_no, line_no, word_no = item[:8]
        normalized = _normalize_token(text)

        if not normalized:
            continue

        tokens.append({
            "text": str(text),
            "normalized": normalized,
            "rect": fitz.Rect(x0, y0, x1, y1),
            "line_key": (int(block_no), int(line_no)),
            "word_no": int(word_no),
        })

    return tokens

def _sentence_tokens(sentence):
    return [
        token
        for token in (_normalize_token(part) for part in str(sentence).split())
        if token
    ]

def _find_token_matches(tokens, pattern):
    matches = []
    pattern_length = len(pattern)

    if not tokens or pattern_length == 0 or pattern_length > len(tokens):
        return matches

    for index in range(0, len(tokens) - pattern_length + 1):
        window = tokens[index:index + pattern_length]
        if [token["normalized"] for token in window] == pattern:
            matches.append(window)

    return matches

def _merge_line_rects(match_tokens):
    lines = {}

    for token in match_tokens:
        key = token["line_key"]
        if key not in lines:
            lines[key] = token["rect"]
        else:
            lines[key].include_rect(token["rect"])

    return list(lines.values())

def _highlight_sentence(doc, sentence, color):
    pattern = _sentence_tokens(sentence)

    if len(pattern) < 2:
        return 0

    total_annotations = 0

    for page_num in range(1, len(doc)):
        page = doc[page_num]
        tokens = _page_tokens(page)
        matches = _find_token_matches(tokens, pattern)

        for match in matches:
            for rect in _merge_line_rects(match):
                annot = page.add_highlight_annot(rect)
                annot.set_colors(stroke=color)
                annot.update()
                total_annotations += 1

    return total_annotations

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

    highlight_stats = {
        "classified_sentences": len(classification_map),
        "matched_sentences": 0,
        "highlight_annotations": 0,
        "unmatched_sentences": [],
    }

    for sentence, label in classification_map.items():
        if label == "Human-written":
            continue
            
        color_hex = COLOR_MAPPING.get(label)
        if not color_hex:
            continue
        color = hex_to_rgb_float(color_hex)

        annotation_count = _highlight_sentence(doc, sentence, color)
        highlight_stats["highlight_annotations"] += annotation_count

        if annotation_count > 0:
            highlight_stats["matched_sentences"] += 1
        else:
            highlight_stats["unmatched_sentences"].append(" ".join(str(sentence).split()))

    doc.set_metadata({
        **doc.metadata,
        "subject": f"Veridity highlight stats: {highlight_stats}",
    })

    out_bytes = doc.write()
    doc.close()
    return BytesIO(out_bytes)
