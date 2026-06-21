import analyze_document


def test_run_document_analysis_returns_insufficient_text_without_score(monkeypatch):
    monkeypatch.setattr(analyze_document, "extract_any_document", lambda *_args: "Only title.")

    result = analyze_document.run_document_analysis(b"%PDF", "pdf", "en")

    assert result["status"] == "insufficient_text"
    assert result["message_key"] == "document_insufficient_text"
    assert "final_score" not in result


def test_run_document_analysis_uses_document_specific_english_labels(monkeypatch):
    text = (
        "I wrote the first observation after comparing the notes from two meetings. "
        "The second paragraph includes a small contradiction, then clarifies the issue using a direct example. "
        "Some sentences are short, while others carry more detail and change rhythm naturally. "
        "The final part explains why the result matters without using the same transition every time."
    )
    monkeypatch.setattr(analyze_document, "extract_any_document", lambda *_args: text)

    result = analyze_document.run_document_analysis(b"%PDF", "pdf", "en")

    assert result["status"] == "success"
    assert result["summary_label"] == "Likely Human"
    assert result["summary_key"] == "document_likely_human"
    assert result["results"]["document"]["verdict"] == "Likely Human"
    assert result["results"]["document"]["engine"] == "lightweight_v2"


def test_run_document_analysis_uses_document_specific_indonesian_labels(monkeypatch):
    text = (
        "Saya menulis pengamatan ini setelah membandingkan catatan dari dua pertemuan. "
        "Bagian berikutnya memiliki contoh yang tidak terlalu rapi, lalu menjelaskan koreksinya secara langsung. "
        "Beberapa kalimat pendek, sementara kalimat lain lebih panjang dan berubah ritmenya. "
        "Kesimpulan akhirnya menjelaskan alasan temuan tersebut penting tanpa mengulang frasa yang sama."
    )
    monkeypatch.setattr(analyze_document, "extract_any_document", lambda *_args: text)

    result = analyze_document.run_document_analysis(b"%PDF", "pdf", "id")

    assert result["status"] == "success"
    assert result["summary_label"] == "Kemungkinan Ditulis Manusia"
    assert result["summary_key"] == "document_likely_human"
