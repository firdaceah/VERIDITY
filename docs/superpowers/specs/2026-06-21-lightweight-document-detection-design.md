# Lightweight Document Detection Design

Date: 2026-06-21

## Goal

Make document analysis stable and fast on Render's free tier by replacing the default RoBERTa-based document detector with a lightweight text-pattern detector. The document result should be presented as a cautious indication, not a hard forensic verdict.

## Decisions

- Use a new default document engine named `lightweight_v2`.
- Do not load RoBERTa, Transformers, or Torch during normal document analysis.
- Keep the existing API response shape as much as possible so Laravel, Flutter, web detail, and PDF report changes stay small.
- Use document-specific labels:
  - `Likely Human`
  - `Mixed Indicators`
  - `Likely AI-Written`
  - `Insufficient Text`
- Indonesian labels:
  - `Kemungkinan Ditulis Manusia`
  - `Indikator Campuran`
  - `Kemungkinan Ditulis AI`
  - `Teks Tidak Cukup`
- If a document has insufficient readable text, the analysis is treated as failed and is not saved to history.

## Scope

Included:

- Python document engine changes.
- Laravel API handling for successful, failed, and insufficient-text document analysis.
- Flutter upload error handling and labels.
- Web detail and PDF report labels for document results.
- Friendly localized error messages in English and Indonesian.

Not included:

- Training a machine learning model.
- Adding OCR for scanned PDFs.
- Keeping RoBERTa as the default document detector.
- Changing image analysis scoring.

## Python Design

The Python service will add a lightweight detector that works from extracted text only.

### Extraction

The existing PDF text extraction remains the first step. After extraction, the detector cleans the text by removing obvious non-content fragments such as page numbers, repeated headers/footers, empty lines, and very short administrative fragments.

### Insufficient Text Gate

Before scoring, Python checks whether the document has enough readable content.

Recommended initial thresholds:

- fewer than 80 valid words, or
- fewer than 4 valid sentences, or
- text extraction returns mostly empty/non-content fragments.

If insufficient, Python returns a machine-readable status such as:

```json
{
  "status": "insufficient_text",
  "message_key": "document_insufficient_text"
}
```

No score is produced for this case.

### Lightweight Scoring

For sufficient text, Python computes several low-memory features:

- sentence length variation
- paragraph length variation
- repeated words and repeated phrases
- lexical diversity
- punctuation and formatting regularity
- overly uniform structure
- AI-like transition/summary phrases
- ratio of short administrative sentences to content sentences

The detector combines these features into a final document score from 0 to 100.

Suggested interpretation:

- `80-100`: `Likely Human`
- `60-79`: `Mixed Indicators`
- `0-59`: `Likely AI-Written`

The exact weights should be calibrated with sample documents from the project before deploy.

### Response Compatibility

Python should return fields compatible with the current document analysis response wherever possible:

- final score
- label/verdict
- detected language if available
- sentence or paragraph indicators
- summary metrics
- explanation keys for localization
- `engine: "lightweight_v2"`

This keeps Laravel and UI changes focused.

## Laravel Design

Laravel remains the main coordinator.

### Success

For successful document analysis, Laravel saves the audit result as before. It stores:

- document score
- document label
- engine name
- summary metrics
- localized explanation keys or translated text

### Insufficient Text

If Python returns `status: insufficient_text`, Laravel treats the analysis as failed:

- no audit history row is created
- no report PDF is generated
- no partial result is shown as a completed analysis
- Flutter receives a friendly localized error

English:

> This document does not contain enough readable text for reliable analysis. Please upload a text-based PDF with more content.

Indonesian:

> Dokumen ini tidak memiliki teks terbaca yang cukup untuk dianalisis dengan andal. Silakan unggah PDF berbasis teks dengan isi yang lebih lengkap.

### Other Python Failures

Laravel should hide technical messages such as Python service names, stack traces, or raw model errors. User-facing errors must remain friendly and localized.

## Flutter Design

Flutter displays document labels using the selected app language.

For `insufficient_text`, Flutter shows the localized error notification from the top and keeps the user on the upload page. The failed document does not appear in history.

Document labels:

- English: `Likely Human`, `Mixed Indicators`, `Likely AI-Written`
- Indonesian: `Kemungkinan Ditulis Manusia`, `Indikator Campuran`, `Kemungkinan Ditulis AI`

Upload guidance should continue to explain that scanned PDFs or slide-export image PDFs are not supported for text analysis.

## Web Detail And PDF Report

For document reports, use document-specific wording instead of image forensic wording.

Examples:

- `Document Text Analysis`
- `Linguistic Pattern Indicators`
- `Likely Human`
- `Mixed Indicators`
- `Likely AI-Written`

If analysis fails with `Insufficient Text`, no report PDF is generated because the audit is not saved.

## Error Handling

All user-facing document errors must be localized.

Required message keys:

- `document_insufficient_text`
- `document_analysis_unavailable`
- `document_analysis_failed`
- `document_file_too_large`
- `document_unsupported_format`

Technical details may be logged server-side but should not be returned to mobile/web users.

## Performance Expectations

The lightweight detector should:

- avoid loading large ML models at app startup
- avoid loading large ML models during document analysis
- run comfortably within Render free-tier memory limits for normal text-based PDFs
- reduce deploy image size if heavy document model dependencies are later removed
- return faster than the current RoBERTa-based pipeline

## Verification Plan

Use these checks before deploy:

- Python import time stays fast and does not load Transformers for document analysis.
- A normal text-based PDF returns one of the three document labels.
- A scanned/image-only PDF returns insufficient text.
- A very short text PDF returns insufficient text.
- Insufficient-text analysis does not create Laravel history.
- Flutter shows a localized top notification for insufficient text.
- Web detail and PDF report use document-specific labels for successful document analysis.
- No user-facing error contains `Python`, stack traces, or raw service details.

## Open Calibration Notes

The first implementation should keep the scoring weights easy to adjust. After implementation, test with:

- one known human-written document
- one AI-written document
- one mixed/edited document
- one short PDF
- one scanned/image PDF

Use those samples to tune thresholds before final Play Store release.
