from fastapi import FastAPI, UploadFile, File, Form, Response
from fastapi.responses import JSONResponse
from analyze_document import run_document_analysis
from analysis.document_pdf_utils import generate_annotated_pdf, extract_any_document
import json
import traceback

app = FastAPI(title="Veridity Document Forensic API")

@app.get("/")
def health_check():
    return {"status": "ok", "message": "Veridity Document API is running"}

@app.post("/analyze-document")
async def analyze_document_endpoint(
    file: UploadFile = File(...), 
    extension: str = Form(...)
):
    try:
        file_bytes = await file.read()
        report = run_document_analysis(file_bytes, extension)
        return JSONResponse(content=report)
    except Exception as e:
        traceback.print_exc()
        return JSONResponse(status_code=500, content={"status": "error", "message": str(e)})
        
@app.post("/generate-pdf-report")
async def generate_pdf_report_endpoint(
    file: UploadFile = File(...),
    classification_map_str: str = Form(...),
    summary_label: str = Form("MIXED TEXT"), 
    audit_id: str = Form("64"),
    analyzed_at: str = Form(None)
):
    try:
        classification_map = json.loads(classification_map_str)
        pdf_bytes = await file.read()
        
        # Memanggil utilitas eksternal yang sudah diperbaiki
        annotated_pdf_stream = generate_annotated_pdf(
            pdf_bytes, 
            classification_map, 
            metadata_summary=summary_label, 
            audit_id=audit_id,
            analyzed_at=analyzed_at
        )
        
        return Response(
            content=annotated_pdf_stream.getvalue(),
            media_type="application/pdf",
            headers={"Content-Disposition": "attachment; filename=annotated_report.pdf"}
        )
    except Exception as e:
        print("\n" + "="*60)
        print("=== DETAIL ERROR INTERNAL PYTHON ===")
        print("="*60)
        traceback.print_exc()
        print("="*60 + "\n")
        return JSONResponse(status_code=500, content={"status": "error", "message": str(e)})