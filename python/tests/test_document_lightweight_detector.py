from analysis.document_detector_core import analyze_text_lightweight_v2, classify_text_hf


def test_lightweight_detector_marks_short_text_as_insufficient():
    result = analyze_text_lightweight_v2("Short title only.")

    assert result["status"] == "insufficient_text"
    assert result["message_key"] == "document_insufficient_text"
    assert "score" not in result


def test_lightweight_detector_returns_likely_human_for_varied_text():
    text = (
        "I started this observation by reading the field notes from the morning session. "
        "Several students used different examples, and their explanations changed as the discussion continued. "
        "One paragraph included a mistake, then a correction, which made the writing feel less uniform. "
        "The conclusion connects the evidence carefully without repeating the same transition in every sentence."
    )

    result = analyze_text_lightweight_v2(text)

    assert result["status"] == "success"
    assert result["label"] == "Likely Human"
    assert result["summary_color"] == "success"
    assert result["score"] >= 80
    assert result["engine"] == "lightweight_v2"


def test_lightweight_detector_returns_mixed_for_borderline_text():
    text = (
        "This document explains the topic in a clear and structured manner. "
        "In addition, the explanation provides several important considerations for the reader. "
        "However, one section includes a specific classroom example with uneven wording and a small correction. "
        "Therefore, the final discussion combines general explanation with some natural variation in style."
    )

    result = analyze_text_lightweight_v2(text)

    assert result["status"] == "success"
    assert result["label"] == "Mixed Indicators"
    assert result["summary_color"] == "warning"
    assert 60 <= result["score"] < 80


def test_classify_text_hf_uses_lightweight_v2_without_loading_hf():
    text = (
        "The report contains a structured explanation of the experiment and its limitations. "
        "Furthermore, the discussion outlines several important implications for future development. "
        "Therefore, the document presents a consistent overview with limited personal variation. "
        "In conclusion, the results indicate that the proposed method can support the analysis process."
    )

    classification_map, percentages = classify_text_hf(text)

    assert classification_map
    assert set(percentages) == {
        "AI-generated",
        "AI-generated & AI-refined",
        "Human-written",
        "Human-written & AI-refined",
    }
