import nltk
import os
import re
from nltk.tokenize import sent_tokenize
from analysis.document_pdf_utils import _is_administrative_sentence

for nltk_path in [
    *os.environ.get("NLTK_DATA", "").split(os.pathsep),
    os.path.join(os.environ.get("APPDATA", ""), "nltk_data"),
    os.path.join(os.environ.get("USERPROFILE", ""), "nltk_data"),
    os.path.join(os.getcwd(), "nltk_data"),
]:
    if nltk_path and os.path.isdir(nltk_path) and nltk_path not in nltk.data.path:
        nltk.data.path.append(nltk_path)

def _sentences(text):
    try:
        return sent_tokenize(text)
    except LookupError:
        return [part.strip() for part in re.split(r"(?<=[.!?])\s+", text) if part.strip()]

def _document_sentences(text):
    return [
        sentence
        for sentence in _sentences(text)
        if not _is_administrative_sentence(sentence)
    ]

def _empty_counts():
    return {
        "AI-generated": 0,
        "AI-generated & AI-refined": 0,
        "Human-written": 0,
        "Human-written & AI-refined": 0,
    }

def _percentages(counts):
    total = sum(counts.values())
    return {
        category: round((count / total) * 100, 2) if total > 0 else 0
        for category, count in counts.items()
    }

def _has_any(text, phrases):
    return any(phrase in text for phrase in phrases)

def _classify_sentence_lightweight(sentence):
    normalized = re.sub(r"\s+", " ", sentence).strip().lower()
    words = re.findall(r"[a-zA-ZÀ-ÿ0-9_]+", normalized)
    word_count = len(words)

    ai_markers = [
        "secara keseluruhan",
        "dengan demikian",
        "oleh karena itu",
        "selain itu",
        "di sisi lain",
        "dalam konteks",
        "dapat disimpulkan",
        "penting untuk",
        "berperan penting",
        "memiliki dampak",
        "secara signifikan",
        "efektif dan efisien",
        "optimal",
        "komprehensif",
    ]
    human_markers = [
        "saya",
        "kami",
        "menurut saya",
        "jawab",
        "diketahui",
        "ditanya",
        "maka",
        "karena",
        "=",
        "->",
    ]

    ai_score = 0.0
    human_score = 0.0

    if word_count >= 28:
        ai_score += 0.35
    elif word_count <= 8:
        human_score += 0.25

    if _has_any(normalized, ai_markers):
        ai_score += 0.45
    if _has_any(normalized, human_markers):
        human_score += 0.35

    punctuation_count = sum(1 for char in sentence if char in ",;:()[]")
    if word_count > 0 and punctuation_count / max(word_count, 1) > 0.12:
        ai_score += 0.15

    unique_ratio = len(set(words)) / max(word_count, 1)
    if word_count >= 14 and unique_ratio > 0.82:
        human_score += 0.15
    elif word_count >= 14 and unique_ratio < 0.58:
        ai_score += 0.15

    if re.search(r"\b\d+\b|[+\-*/=<>]", normalized):
        human_score += 0.20

    score = ai_score - human_score
    if score >= 0.45:
        return "AI-generated"
    if score >= 0.12:
        return "AI-generated & AI-refined"
    if score <= -0.30:
        return "Human-written"
    return "Human-written & AI-refined"

def _classify_text_lightweight(text):
    sentences = _document_sentences(text)
    classification_map = {}
    counts = _empty_counts()

    for sentence in sentences:
        label = _classify_sentence_lightweight(sentence)
        classification_map[sentence] = label
        counts[label] += 1

    return classification_map, _percentages(counts)

def _classify_text_with_hf(text, threshold):
    from analysis.document_model_loaders import load_detector_model

    detector = load_detector_model()
    sentences = _document_sentences(text)
    results = detector(sentences, truncation=True, batch_size=1)

    classification_map = {}
    counts = _empty_counts()

    for sentence, result in zip(sentences, results):
        label = result['label'].upper()
        score = result['score']
        if label == "FAKE":
            if score >= threshold:
                new_label = "AI-generated"
            else:
                new_label = "AI-generated & AI-refined"
        elif label == "REAL":
            if score >= threshold:
                new_label = "Human-written"
            else:
                new_label = "Human-written & AI-refined"
        else:
            new_label = "Human-written"
        classification_map[sentence] = new_label
        counts[new_label] += 1

    return classification_map, _percentages(counts)

def _words(text):
    return re.findall(r"[a-zA-ZÀ-ÿ0-9_]+", text.lower())

def _valid_document_stats(text):
    sentences = [
        sentence.strip()
        for sentence in _document_sentences(text)
        if len(_words(sentence)) >= 3
    ]
    words = _words(" ".join(sentences))
    return sentences, words

def _phrase_repetition_ratio(words, size=3):
    if len(words) < size * 2:
        return 0.0

    phrases = [" ".join(words[index:index + size]) for index in range(len(words) - size + 1)]
    unique_phrases = set(phrases)
    return 1.0 - (len(unique_phrases) / max(len(phrases), 1))

def _coefficient_of_variation(values):
    if not values:
        return 0.0

    average = sum(values) / len(values)
    if average == 0:
        return 0.0

    variance = sum((value - average) ** 2 for value in values) / len(values)
    return (variance ** 0.5) / average

def _marker_count(text, markers):
    normalized = re.sub(r"\s+", " ", text.lower())
    return sum(1 for marker in markers if marker in normalized)

def _lightweight_document_score(text, sentences, words):
    normalized = re.sub(r"\s+", " ", text.lower())
    sentence_lengths = [len(_words(sentence)) for sentence in sentences]
    length_variation = _coefficient_of_variation(sentence_lengths)
    unique_ratio = len(set(words)) / max(len(words), 1)
    repetition_ratio = _phrase_repetition_ratio(words)

    ai_markers = [
        "in conclusion",
        "furthermore",
        "moreover",
        "therefore",
        "overall",
        "it is important",
        "structured manner",
        "important considerations",
        "secara keseluruhan",
        "dengan demikian",
        "oleh karena itu",
        "selain itu",
        "di sisi lain",
        "dalam konteks",
        "dapat disimpulkan",
        "penting untuk",
        "berperan penting",
        "secara signifikan",
        "komprehensif",
    ]
    natural_markers = [
        "i ",
        "my ",
        "we ",
        "mistake",
        "correction",
        "catatan",
        "contoh",
        "koreksi",
        "saya",
        "kami",
        "menurut",
        "tidak terlalu rapi",
    ]

    ai_marker_count = _marker_count(normalized, ai_markers)
    natural_marker_count = _marker_count(normalized, natural_markers)

    score = 84.0
    score += min(length_variation, 0.65) * 22.0
    score += min(unique_ratio, 0.9) * 7.0
    score += min(natural_marker_count, 4) * 2.0
    score -= min(ai_marker_count, 5) * 5.0
    score -= min(repetition_ratio * 100.0, 18.0)

    if length_variation < 0.18 and len(sentences) >= 4:
        score -= 12.0
    if unique_ratio < 0.55:
        score -= 8.0
    if ai_marker_count >= 3 and natural_marker_count <= 1:
        score -= 4.0

    return max(0.0, min(100.0, round(score, 2)))

def _label_for_score(score):
    if score >= 80.0:
        return "Likely Human", "success"
    if score >= 60.0:
        return "Mixed Indicators", "warning"
    return "Likely AI-Written", "danger"

def analyze_text_lightweight_v2(text):
    sentences, words = _valid_document_stats(text or "")

    if len(words) < 35 or len(sentences) < 4:
        return {
            "status": "insufficient_text",
            "message_key": "document_insufficient_text",
            "engine": "lightweight_v2",
            "metrics": {
                "valid_word_count": len(words),
                "valid_sentence_count": len(sentences),
            },
        }

    classification_map, percentages = _classify_text_lightweight(text)
    score = _lightweight_document_score(text, sentences, words)
    label, color = _label_for_score(score)

    return {
        "status": "success",
        "engine": "lightweight_v2",
        "score": score,
        "label": label,
        "summary_color": color,
        "classification_map": classification_map,
        "percentages": percentages,
        "metrics": {
            "valid_word_count": len(words),
            "valid_sentence_count": len(sentences),
            "human_p": round(percentages.get("Human-written", 0.0), 2),
            "ai_p": round(percentages.get("AI-generated", 0.0), 2),
            "hybrid_p": round(
                percentages.get("AI-generated & AI-refined", 0.0)
                + percentages.get("Human-written & AI-refined", 0.0),
                2,
            ),
        },
    }

def classify_text_hf(text, threshold=0.8):
    """
    Splits text into sentences and classifies each sentence.
    Compatibility wrapper for the document pipeline. The default detector is
    lightweight_v2 so Render free-tier deployments do not load RoBERTa.
    """
    result = analyze_text_lightweight_v2(text)
    if result["status"] != "success":
        return {}, _empty_counts()
    return result["classification_map"], result["percentages"]
