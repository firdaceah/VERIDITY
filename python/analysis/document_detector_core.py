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

def classify_text_hf(text, threshold=0.8):
    """
    Splits text into sentences and classifies each sentence.
    Uses the original roberta-base-openai-detector pipeline.
    """
    return _classify_text_with_hf(text, threshold)
