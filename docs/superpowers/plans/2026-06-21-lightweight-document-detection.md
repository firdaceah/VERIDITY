# Lightweight Document Detection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the default heavy RoBERTa document detector with a lightweight text-pattern detector and treat insufficient-text documents as failed analyses that are not saved to history.

**Architecture:** Python owns PDF text extraction, insufficient-text gating, and lightweight scoring. Laravel coordinates upload, maps Python statuses into localized API responses, and only creates history/report records for successful document analyses. Flutter/web/PDF consume the existing response shape with document-specific labels.

**Tech Stack:** Python/FastAPI/PyMuPDF/NLTK, Laravel/Pest/HTTP fakes, Flutter/Dart.

---

### Task 1: Python Lightweight Document Detector

**Files:**
- Modify: `python/analysis/document_detector_core.py`
- Create: `python/tests/test_document_lightweight_detector.py`

- [ ] **Step 1: Write failing tests for insufficient text and cautious labels**

Create `python/tests/test_document_lightweight_detector.py`:

```python
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
```

- [ ] **Step 2: Run Python tests and verify RED**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\python
python -m pytest tests/test_document_lightweight_detector.py -q --no-cov
```

Expected: fails because `analyze_text_lightweight_v2` does not exist.

- [ ] **Step 3: Implement lightweight detector**

Modify `python/analysis/document_detector_core.py`:

- add `analyze_text_lightweight_v2(text)`
- keep `classify_text_hf(text, threshold=0.8)` as a compatibility wrapper
- remove default Hugging Face loading from the normal document path
- return `insufficient_text` when valid word/sentence thresholds are not met
- return compatible `classification_map` and percentages for sufficient text

- [ ] **Step 4: Run Python tests and verify GREEN**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\python
python -m pytest tests/test_document_lightweight_detector.py -q --no-cov
```

Expected: all tests pass.

### Task 2: Python Document Pipeline Response

**Files:**
- Modify: `python/analyze_document.py`
- Create: `python/tests/test_analyze_document_lightweight.py`

- [ ] **Step 1: Write failing tests for pipeline labels and insufficient text**

Create `python/tests/test_analyze_document_lightweight.py`:

```python
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
```

- [ ] **Step 2: Run tests and verify RED**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\python
python -m pytest tests/test_analyze_document_lightweight.py -q --no-cov
```

Expected: fails because the current pipeline still returns `error` for short text and old document labels.

- [ ] **Step 3: Implement pipeline response mapping**

Modify `python/analyze_document.py`:

- import `analyze_text_lightweight_v2`
- add localized messages for `document_insufficient_text`
- return `status: insufficient_text` directly when the detector returns insufficient text
- map labels to English/Indonesian text
- include `engine: lightweight_v2` in result metadata
- keep `classification_map`, `metrics`, and `final_score` for successful analyses

- [ ] **Step 4: Run tests and verify GREEN**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\python
python -m pytest tests/test_analyze_document_lightweight.py tests/test_document_lightweight_detector.py -q --no-cov
```

Expected: all tests pass.

### Task 3: Laravel Insufficient Text Handling

**Files:**
- Modify: `veridity-laravel/app/Http/Controllers/Api/ForensicController.php`
- Modify: `veridity-laravel/tests/Feature/ApiAuditTest.php`

- [ ] **Step 1: Write failing Pest test**

Append to `veridity-laravel/tests/Feature/ApiAuditTest.php`:

```php
test('insufficient text document analysis fails without creating history', function () {
    config()->set('services.veridity.python_engine_url', 'http://python-engine.test');
    Http::fake([
        'http://python-engine.test/analyze-document' => Http::response([
            'status' => 'insufficient_text',
            'message_key' => 'document_insufficient_text',
        ]),
    ]);

    $user = User::factory()->create();
    $file = UploadedFile::fake()->createWithContent('short.pdf', '%PDF-1.4 short');

    $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/audits', [
            'image' => $file,
            'language' => 'en',
        ])
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'This document does not contain enough readable text for reliable analysis. Please upload a text-based PDF with more content.');

    expect(ForensicAnalysis::query()->count())->toBe(0);
});
```

- [ ] **Step 2: Run Laravel test and verify RED**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity-laravel
php artisan test tests/Feature/ApiAuditTest.php --filter="insufficient text document analysis fails"
```

Expected: fails because Laravel currently treats non-success document responses as a generic server error.

- [ ] **Step 3: Implement Laravel mapping**

Modify `ForensicController.php`:

- add localized `document_insufficient_text`
- detect `$result['status'] === 'insufficient_text'` before generic error handling
- delete the temporary uploaded file before returning
- return HTTP 422 with the friendly localized message
- do not create `ForensicAnalysis`

- [ ] **Step 4: Run Laravel tests and verify GREEN**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity-laravel
php artisan test tests/Feature/ApiAuditTest.php --filter="document"
```

Expected: document-related tests pass.

### Task 4: Document Labels In UI And Reports

**Files:**
- Modify: `veridity_mobile/lib/features/audit/domain/entities/audit_entity.dart`
- Modify: `veridity_mobile/lib/features/audit/presentation/pages/audit_detail_page.dart`
- Modify: `veridity_mobile/lib/features/audit/presentation/pages/history_page.dart`
- Modify: `veridity-laravel/resources/views/user/pdf-report.blade.php`
- Modify: `veridity-laravel/resources/views/user/my-audits.blade.php`

- [ ] **Step 1: Search current old document label usage**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY
rg -n "MIXED TEXT|AI ASSISTED|MOSTLY AI|AUTHENTIC \\(HUMAN|TEKS CAMPURAN|MAYORITAS AI|DITULIS MANUSIA|Human-written|AI-generated" veridity_mobile veridity-laravel\resources\views
```

- [ ] **Step 2: Update label mapping**

Replace old document-facing labels with:

- English: `Likely Human`, `Mixed Indicators`, `Likely AI-Written`
- Indonesian: `Kemungkinan Ditulis Manusia`, `Indikator Campuran`, `Kemungkinan Ditulis AI`

Keep sentence-level highlight labels only where they describe classifier internals.

- [ ] **Step 3: Run static checks**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity_mobile
dart analyze lib\features\audit\presentation\pages\audit_detail_page.dart lib\features\audit\presentation\pages\history_page.dart lib\features\audit\domain\entities\audit_entity.dart
```

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity-laravel
php -l resources\views\user\pdf-report.blade.php
```

Expected: no syntax/analyze errors.

### Task 5: End-To-End Verification

**Files:**
- No new files.

- [ ] **Step 1: Verify Python import stays lightweight**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\python
python -c "import time; t=time.time(); import main_api; print(round(time.time()-t, 2))"
```

Expected: import completes quickly without loading the RoBERTa model.

- [ ] **Step 2: Run focused backend tests**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\python
python -m pytest tests/test_document_lightweight_detector.py tests/test_analyze_document_lightweight.py tests/test_document_pdf_utils.py -q --no-cov
```

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY\veridity-laravel
php artisan test tests/Feature/ApiAuditTest.php --filter="document"
```

Expected: all focused tests pass.

- [ ] **Step 3: Confirm no technical Python message leaks to user-facing upload paths**

Run:

```powershell
cd C:\Users\user\PENS\Semester-4\VERIDITY
rg -n "Python gagal|Layanan analisis gambar Python|document forensic module|A system failure occurred" veridity_mobile veridity-laravel\app\Http\Controllers\Api\ForensicController.php python\analyze_document.py
```

Expected: no user-facing technical error remains.
