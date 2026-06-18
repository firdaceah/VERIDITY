from fastapi.testclient import TestClient

from analyze_document import run_document_analysis
from main_api import app


def test_document_analysis_returns_english_cancelled_message_before_work():
    result = run_document_analysis(
        b"%PDF-1.4 sample",
        "pdf",
        language="en",
        is_cancelled=lambda: True,
    )

    assert result["status"] == "cancelled"
    assert result["message"] == "Analysis was cancelled."


def test_document_analysis_returns_indonesian_cancelled_message_before_work():
    result = run_document_analysis(
        b"%PDF-1.4 sample",
        "pdf",
        language="id",
        is_cancelled=lambda: True,
    )

    assert result["status"] == "cancelled"
    assert result["message"] == "Analisis dibatalkan."


def test_cancel_analysis_endpoint_accepts_indonesian_language():
    client = TestClient(app)

    response = client.post(
        "/cancel-analysis",
        json={"analysis_token": "token-123", "language": "id"},
    )

    assert response.status_code == 200
    assert response.json() == {
        "status": "success",
        "message": "Permintaan pembatalan analisis diterima.",
    }
