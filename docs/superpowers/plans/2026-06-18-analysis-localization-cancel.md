# Analysis Localization and Server-Side Cancellation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make analysis messages follow English/Indonesian selection and allow users to cancel long analysis runs through Laravel and Python services deployed on Render.

**Architecture:** Mobile sends `language` and `analysis_token` with each upload. Laravel stores cancellation state in cache, forwards language/token to Python, and exposes a cancel endpoint. Python exposes a cancel endpoint and checks token cancellation between major analysis steps.

**Tech Stack:** Flutter/Dart, Laravel/PHP, FastAPI/Python, Laravel cache, HTTP multipart APIs.

---

### Task 1: Laravel Localization and Cancel Contract

**Files:**
- Modify: `veridity-laravel/app/Http/Controllers/Api/ForensicController.php`
- Modify: `veridity-laravel/routes/api.php`
- Test: `veridity-laravel/tests/Feature/ApiAuditTest.php`

- [ ] Add tests that `POST /api/audits/cancel` returns English by default and Indonesian when `language=id`.
- [ ] Add language helpers in `ForensicController`: normalize `en|id`, translate message keys, and translate summary labels.
- [ ] Add cache helpers for `analysis_token`: cancelled key, mark cancelled, check cancelled.
- [ ] Add `cancelAnalysis(Request $request)` endpoint.
- [ ] Pass `language` and `analysis_token` to Python `/analyze-document`, `/analyze-image`, and `/cancel-analysis`.
- [ ] Check cancelled state before analysis starts, after Python returns, and before creating `ForensicAnalysis`.

### Task 2: Python Localization and Cancellation

**Files:**
- Modify: `python/main_api.py`
- Modify: `python/analyze_all.py`
- Modify: `python/analyze_document.py`
- Test: `python/tests/test_analysis_cancellation.py`

- [ ] Add tests for English/Indonesian message fallback and cancellation checkpoints.
- [ ] Add in-memory cancellation registry in `main_api.py` with `/cancel-analysis`.
- [ ] Accept `language` and `analysis_token` form fields on `/analyze-image` and `/analyze-document`.
- [ ] Pass a cancellation callback to `run_full_investigation_quiet` and `run_document_analysis`.
- [ ] Add checkpoint checks between metadata, ELA, AI detection, noise, and final assembly for image analysis.
- [ ] Add checkpoint checks around document extraction/classification/summary generation.

### Task 3: Flutter Upload Cancel UI and Request Contract

**Files:**
- Modify: `veridity_mobile/lib/core/widgets/analysis_loading_screen.dart`
- Modify: `veridity_mobile/lib/core/network/api_client.dart`
- Modify: `veridity_mobile/lib/features/audit/data/repositories/audit_repository.dart`
- Modify: `veridity_mobile/lib/features/audit/presentation/pages/upload_file_page.dart`

- [ ] Generate a unique analysis token before upload.
- [ ] Send `language` and `analysis_token` in multipart upload.
- [ ] Add repository method for `cancelAnalysis(token, language)`.
- [ ] Add close icon and confirmation dialog to `AnalysisLoadingScreen`.
- [ ] On confirmed cancel, call Laravel cancel endpoint, stop loading UI, and show localized cancellation message.
- [ ] Ignore late upload completion if user has cancelled.

### Task 4: Verification

**Files:**
- Test commands only.

- [ ] Run Laravel targeted tests: `php artisan test tests/Feature/ApiAuditTest.php`.
- [ ] Run Python targeted tests: `pytest tests/test_analysis_cancellation.py`.
- [ ] Run Flutter analyzer or targeted tests if available: `flutter analyze`.
- [ ] Review changed files for accidental unrelated edits.
