import fitz
from io import BytesIO
from datetime import datetime
import nltk
from nltk.tokenize import word_tokenize
from docx import Document  
import os
import re
import textwrap
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
        return normalize_document_text(extract_text_from_pdf(file_bytes))
    elif ext == 'docx':
        return normalize_document_text(extract_text_from_docx(file_bytes))
    else:
        raise ValueError(f"Format dokumen '{ext}' tidak didukung oleh sistem Veridity!")

def normalize_document_text(text):
    text = unicodedata.normalize("NFKC", str(text))
    text = re.sub(r"-\s*\n\s*", "", text)
    text = re.sub(r"\s*\n\s*", " ", text)
    text = re.sub(r"\s+", " ", text)
    text = re.sub(r"\s+([,.;:!?])", r"\1", text)
    return text.strip()

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

ADMINISTRATIVE_PATTERNS = [
    r"\bnama\s+(dosen|mahasiswa|anggota|penyusun|kampus)\b",
    r"\b(dosen|mahasiswa|kampus|universitas|politeknik|institut|sekolah tinggi|pens)\b",
    r"\b(nim|nrp|nip|kelas|program studi|prodi|jurusan|fakultas)\b",
    r"\b(disusun oleh|diajukan kepada|mata kuliah|lembar pengesahan|cover|dosen pengampu|kelompok)\b",
    r"\b\d{8,}\b",
]

def _is_administrative_sentence(sentence):
    normalized = normalize_document_text(sentence).lower()
    words = normalized.split()

    if not normalized:
        return True

    if len(words) <= 12 and re.search(r"\b(nama|nim|nrp|nip|kelas|prodi|jurusan|kelompok|dosen|disusun)\b", normalized):
        return True

    if len(words) <= 24 and re.search(r"\b\d{8,}\b", normalized):
        return True

    if len(words) <= 24 and re.search(r"\b(program studi|politeknik|universitas|departemen|informatika|surabaya)\b", normalized):
        return True

    return any(re.search(pattern, normalized) for pattern in ADMINISTRATIVE_PATTERNS)

def _is_administrative_page(page):
    normalized = normalize_document_text(page.get_text("text")).lower()

    if not normalized:
        return False

    marker_count = sum(
        1
        for marker in [
            "dosen pengampu",
            "disusun oleh",
            "kelompok",
            "program studi",
            "politeknik",
            "universitas",
            "departemen",
            "nim",
            "nrp",
        ]
        if marker in normalized
    )

    return marker_count >= 2

def _highlight_sentence(doc, sentence, color):
    pattern = _sentence_tokens(sentence)

    if len(pattern) < 2:
        return 0

    total_annotations = 0

    for page_num in range(1, len(doc)):
        page = doc[page_num]
        if _is_administrative_page(page):
            continue

        tokens = _page_tokens(page)
        matches = _find_token_matches(tokens, pattern)

        for match in matches:
            for rect in _merge_line_rects(match):
                expanded = fitz.Rect(rect)
                expanded.x0 -= 1.5
                expanded.y0 -= 1.5
                expanded.x1 += 1.5
                expanded.y1 += 1.5
                page.draw_rect(
                    expanded,
                    color=None,
                    fill=color,
                    fill_opacity=0.34,
                    overlay=True,
                )
                annot = page.add_highlight_annot(rect)
                annot.set_colors(stroke=color)
                annot.update()
                total_annotations += 1

    return total_annotations

def _pdf_from_text(text):
    doc = fitz.open()
    lines = []
    for paragraph in normalize_document_text(text).split("\n"):
        wrapped = textwrap.wrap(paragraph, width=92) or [""]
        lines.extend(wrapped)

    page = None
    y = 58

    for line in lines:
        if page is None or y > 790:
            page = doc.new_page(width=595, height=842)
            y = 58

        page.insert_text(
            fitz.Point(50, y),
            line,
            fontsize=11,
            fontname="helvetica",
            color=(31/255, 41/255, 55/255),
        )
        y += 17

    out = doc.write()
    doc.close()
    return out

def _source_pdf_bytes(file_bytes, source_extension):
    ext = str(source_extension or "pdf").lower().lstrip(".")
    if ext == "docx":
        return _pdf_from_text(extract_text_from_docx(file_bytes))
    return file_bytes

def _percentage(value):
    try:
        return f"{float(value):.2f}%"
    except (TypeError, ValueError):
        return "0.00%"

def _normalize_language(language):
    return "id" if str(language).lower() == "id" else "en"


def _tr(language, en, id_text):
    return id_text if _normalize_language(language) == "id" else en


def _draw_nlp_metrics(page, metrics, interpretation, language="en"):
    metrics = metrics or {}
    page.draw_rect(fitz.Rect(50, 440, 545, 460), color=None, fill=(248/255, 250/255, 252/255))
    page.insert_text(fitz.Point(55, 454), _tr(language, "LANGUAGE COMPUTATION DETAILS (NLP METRICS)", "RINCIAN KOMPUTASI BAHASA (NLP METRICS)"), fontsize=10, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    rows = [
        (_tr(language, "Original Sentences (Human-written)", "Kalimat Orisinal (Human-written)"), metrics.get("human_p", 0)),
        (_tr(language, "Synthetic Sentences (AI-generated)", "Kalimat Sintetis (AI-generated)"), metrics.get("ai_p", 0)),
        (_tr(language, "Hybrid / AI-refined Sentences", "Kalimat Hybrid / AI-refined"), metrics.get("hybrid_p", 0)),
    ]

    for index, (label, value) in enumerate(rows):
        y = 485 + (index * 25)
        page.insert_text(fitz.Point(65, y), label, fontsize=10, fontname="helvetica", color=(51/255, 65/255, 85/255))
        page.insert_text(fitz.Point(405, y), _percentage(value), fontsize=10, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))

    if interpretation:
        page.draw_rect(fitz.Rect(55, 560, 540, 630), color=(226/255, 232/255, 240/255), fill=(248/255, 250/255, 252/255))
        page.insert_textbox(
            fitz.Rect(65, 572, 530, 620),
            str(interpretation),
            fontsize=9,
            fontname="helvetica-oblique",
            color=(71/255, 85/255, 105/255),
        )

    page.draw_rect(fitz.Rect(55, 650, 540, 720), color=(226/255, 232/255, 240/255), fill=(255/255, 247/255, 237/255))
    formula = _tr(
        language,
        "Decision formula: Human Score = percentage of Human-written sentences. >= 80%: Authentic, 60-79%: Mixed AI Assisted, < 60%: Mostly AI Generated.",
        "Rumus keputusan: Human Score = persentase kalimat Human-written. >= 80%: Aman, 60-79%: Mixed AI Assisted, < 60%: Mayoritas AI Generated.",
    )
    page.insert_textbox(
        fitz.Rect(65, 662, 530, 710),
        formula,
        fontsize=9,
        fontname="helvetica",
        color=(124/255, 45/255, 18/255),
    )

def generate_annotated_pdf(
    pdf_bytes,
    classification_map,
    metadata_summary="MIXED TEXT (AI ASSISTED)",
    audit_id="61",
    analyzed_at=None,
    document_metrics=None,
    interpretation=None,
    source_extension="pdf",
    language="en",
):
    """
    Menghasilkan laporan PDF formal dengan menyisipkan Kop Surat Veridity PENS 
    di halaman pertama, lalu memberikan warna stabilo AI pada teks di halaman berikutnya.
    """
    language = _normalize_language(language)
    report_title = _tr(language, "Veridity Forensic Investigation Report", "Laporan Investigasi Forensik Veridity")
    doc = fitz.open(stream=_source_pdf_bytes(pdf_bytes, source_extension), filetype="pdf")
    doc.set_metadata({**doc.metadata, "title": report_title})
    kop_page = doc.new_page(pno=0, width=595, height=842) 
    
    # Desain Garis Pembatas Kop Surat
    kop_page.draw_line(fitz.Point(50, 85), fitz.Point(545, 85), color=(30/255, 58/255, 138/255), width=2)
    
    # Tulis Text Kop Surat - Menggunakan fontname standar langsung tanpa init_font
    kop_page.insert_text(fitz.Point(50, 65), "VeriDity.", fontsize=22, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    kop_page.insert_text(fitz.Point(340, 65), _tr(language, "DOCUMENT DIGITAL INTEGRITY REPORT", "LAPORAN INTEGRITAS DIGITAL DOKUMEN"), fontsize=11, fontname="helvetica-bold", color=(100/255, 116/255, 139/255))
    
    # Judul Seksi Informasi Master
    kop_page.draw_rect(fitz.Rect(50, 110, 545, 130), color=None, fill=(248/255, 250/255, 252/255))
    kop_page.insert_text(fitz.Point(55, 124), _tr(language, "DOCUMENT EVIDENCE INFORMATION", "INFORMASI DOKUMEN BARANG BUKTI DOKUMEN"), fontsize=10, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    
    if not analyzed_at:
        analyzed_at = datetime.now().strftime("%d %b %Y, %H:%M") + " WIB"
        
    kop_page.insert_text(fitz.Point(55, 160), f"{_tr(language, 'Investigation Code', 'Kode Investigasi')}    :  #VRD-{audit_id}", fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 185), f"{_tr(language, 'Analysis Time', 'Waktu Analisis')}       :  {analyzed_at}", fontsize=11, fontname="helvetica")
    format_label = str(source_extension or "pdf").upper().lstrip(".")
    object_family = _tr(language, "Linguistic Text Family", "Rumpun Linguistic Teks")
    kop_page.insert_text(fitz.Point(55, 210), f"{_tr(language, 'Object Format', 'Format Objek')}        :  {format_label} ({object_family})", fontsize=11, fontname="helvetica")
    
    kop_page.insert_text(fitz.Point(55, 238), f"{_tr(language, 'Final File Verdict', 'Vonis Akhir Berkas')} : ", fontsize=11, fontname="helvetica")
    
    # Sinkronisasi Warna Badge Status
    status_upper = str(metadata_summary).upper()
    badge_color = (22/255, 163/255, 74/255) # Default: Hijau
    
    if "MAYORITAS" in status_upper or "FULL" in status_upper or "FORGED" in status_upper:
        badge_color = (220/255, 38/255, 38/255) # Red
    elif "AI" in status_upper or "MIXED" in status_upper:
        badge_color = (234/255, 88/255, 12/255) # Orange

    kop_page.draw_rect(fitz.Rect(175, 224, 380, 246), color=badge_color, fill=badge_color, radius=None)
    kop_page.insert_text(fitz.Point(185, 240), metadata_summary, fontsize=10, fontname="helvetica-bold", color=(1,1,1))

    # Teks Legenda Panduan Warna Stabilo
    kop_page.draw_rect(fitz.Rect(50, 280, 545, 300), color=None, fill=(248/255, 250/255, 252/255))
    kop_page.insert_text(fitz.Point(55, 294), _tr(language, "SENTENCE DISTRIBUTION ANNOTATION COLOR GUIDE (AI METRICS)", "PANDUAN WARNA ANOTASI SEBARAN KALIMAT (AI METRICS)"), fontsize=10, fontname="helvetica-bold", color=(30/255, 58/255, 138/255))
    
    kop_page.insert_text(fitz.Point(55, 330), _tr(language, "- Pink       :  Strongly indicated as AI-generated text", "- Merah Muda (Pink)       :  Terindikasi Kuat Buatan Mesin Generatif (AI-Generated)"), fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 355), _tr(language, "- Orange     :  Machine paraphrase / AI-refined text", "- Jingga (Orange)            :  Hasil Parafrase / Refinement Mesin Komputer (AI-Refined)"), fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 380), _tr(language, "- Light Blue :  Combined human and AI-edited text", "- Biru Muda (Light Blue)  :  Teks Gabungan / Hasil Editan Manusia & AI"), fontsize=11, fontname="helvetica")
    kop_page.insert_text(fitz.Point(55, 405), _tr(language, "- No highlight: Natural human-written style", "- Tanpa Warna Stabilo     :  Murni Gaya Tulisan Alami Manusia (Human-written)"), fontsize=11, fontname="helvetica")

    _draw_nlp_metrics(kop_page, document_metrics, interpretation, language=language)

    # Catatan Kaki Validasi Kampus PENS
    kop_page.draw_line(fitz.Point(50, 780), fitz.Point(545, 780), color=(226/255, 232/255, 240/255), width=1)
    kop_page.insert_text(fitz.Point(50, 795), _tr(language, "This document was issued by the Veridity Forensic Platform. Generated automatically through the Python Engine Gateway.", "Dokumen ini diterbitkan oleh Veridity Platform Forensik. Dicetak otomatis via Python Engine Gateway."), fontsize=8, fontname="helvetica", color=(148/255, 163/255, 184/255))
    kop_page.insert_text(fitz.Point(50, 808), _tr(language, "Politeknik Elektronika Negeri Surabaya (PENS) - Informatics Engineering Department", "Politeknik Elektronika Negeri Surabaya (PENS) - Jurusan Teknik Informatika"), fontsize=8, fontname="helvetica-bold", color=(148/255, 163/255, 184/255))

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
        if _is_administrative_sentence(sentence):
            highlight_stats.setdefault("skipped_administrative", []).append(" ".join(str(sentence).split()))
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
        "title": report_title,
        "subject": f"Veridity highlight stats: {highlight_stats}",
    })

    out_bytes = doc.write()
    doc.close()
    return BytesIO(out_bytes)
