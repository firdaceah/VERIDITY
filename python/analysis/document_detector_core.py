import nltk
import os
import re
from nltk.tokenize import sent_tokenize
from analysis.document_model_loaders import load_detector_model

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

def classify_text_hf(text, threshold=0.8):
    """
    Splits text into sentences, uses roberta-base-openai-detector to classify each sentence
    as AI-generated or human-written, returning a map of {sentence: label} and overall percentages.
    """
    detector = load_detector_model()
    sentences = _sentences(text)
    results = detector(sentences, truncation=True)

    classification_map = {}
    counts = {
        "AI-generated": 0,
        "AI-generated & AI-refined": 0,
        "Human-written": 0,
        "Human-written & AI-refined": 0
    }

    for sentence, result in zip(sentences, results):
        label = result['label'].upper()  # "FAKE" or "REAL"
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

    total = sum(counts.values())
    percentages = {
        cat: round((count / total)*100, 2) if total > 0 else 0
        for cat, count in counts.items()
    }
    return classification_map, percentages
