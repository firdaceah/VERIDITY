# Analysis Localization and Server-Side Cancellation Design

## Context

VERIDITY has three deployed pieces that participate in analysis:

- Flutter mobile app for upload, loading, results, and language selection.
- Laravel API on Render for validation, persistence, orchestration, and report generation.
- Python FastAPI engine on Render for image and document forensic processing.

The app supports two languages: English and Indonesian. English is the default.

## Goals

- Analysis success messages, error messages, summary labels, and interpretation text follow the selected language.
- Mobile sends the selected language to Laravel for every analysis request.
- Laravel forwards the selected language to Python for remote Render analysis.
- The loading screen has an `X` action in the top-left corner.
- Pressing `X` asks for confirmation before cancellation.
- If confirmed, cancellation reaches Laravel and Python, not only the mobile UI.

## Non-Goals

- No full queue-worker migration in this change.
- No destructive cleanup of already completed audits if analysis finishes before cancellation is processed.
- No new language beyond English and Indonesian.

## Design

Mobile generates an `analysis_token` for each upload and sends it with `language=en|id` in the multipart request. The loading screen receives an `onCancel` callback and displays a close icon. Confirmation text is localized by `AppLanguage`.

Laravel accepts `language` and `analysis_token`, defaults language to English, and stores cancellation state in the cache. Laravel exposes an authenticated cancel endpoint such as `POST /api/audits/cancel` with the token. When called, Laravel marks the token as cancelled in cache and forwards a cancel request to Python when the configured Python engine is remote.

For image and document calls, Laravel forwards `language` and `analysis_token` to Python. Laravel checks cancellation before starting, after Python returns, and before saving a final audit. Cancelled analysis returns `status=cancelled` and a localized message.

Python accepts `language` and `analysis_token`. It exposes a cancel endpoint and stores cancelled tokens in process memory with a bounded lifetime. The image and document pipelines call a cancellation checkpoint between major steps. On cancellation, Python returns `status=cancelled` and a localized message. This works on Render because Laravel and Python communicate through HTTP, not local process handles.

## Error Handling

Laravel owns API validation and orchestration messages. Python owns engine-level messages. Both use small explicit dictionaries for `en` and `id`; unknown or missing language falls back to English.

If Python cannot be reached during cancellation, Laravel still marks the token as cancelled locally and returns success for the cancel request. The running remote process may stop at Python's next checkpoint only if the cancel request reached Python.

## Testing

- Laravel feature tests cover localized validation/cancel response shape.
- Python tests cover language fallback and cancellation checkpoints.
- Flutter/widget or unit coverage focuses on passing `language` and `analysis_token`, plus cancel confirmation behavior where practical.
