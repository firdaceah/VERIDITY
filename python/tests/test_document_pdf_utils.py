import fitz

from analysis.document_pdf_utils import generate_annotated_pdf


def _sample_pdf_bytes():
    doc = fitz.open()
    page = doc.new_page()
    page.insert_text(
        fitz.Point(72, 72),
        "Kalimat ini dibuat oleh AI untuk menguji laporan Veridity.",
        fontsize=12,
    )
    page.insert_text(
        fitz.Point(72, 104),
        "Kalimat kedua ini murni ditulis manusia.",
        fontsize=12,
    )
    payload = doc.write()
    doc.close()
    return payload


def _annotation_count(pdf_bytes):
    doc = fitz.open(stream=pdf_bytes, filetype="pdf")
    total = 0
    for page in doc:
        annotations = page.annots()
        if annotations:
            total += sum(1 for _ in annotations)
    doc.close()
    return total


def test_generate_annotated_pdf_highlights_non_human_sentence():
    result = generate_annotated_pdf(
        _sample_pdf_bytes(),
        {
            "Kalimat ini dibuat oleh AI untuk menguji laporan Veridity.": "AI-generated",
        },
    )

    assert _annotation_count(result.getvalue()) >= 1


def test_generate_annotated_pdf_skips_human_written_sentence():
    result = generate_annotated_pdf(
        _sample_pdf_bytes(),
        {
            "Kalimat kedua ini murni ditulis manusia.": "Human-written",
        },
    )

    assert _annotation_count(result.getvalue()) == 0
