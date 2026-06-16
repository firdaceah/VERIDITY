from fastapi import FastAPI, UploadFile, File, Form, Response
from fastapi.responses import JSONResponse
from analyze_all import run_full_investigation_quiet
from analyze_document import run_document_analysis
from analysis.document_pdf_utils import generate_annotated_pdf, extract_any_document
import base64
import json
import os
import tempfile
import traceback
from PIL import Image, ImageOps

app = FastAPI(title="Veridity Document Forensic API")

def prepare_image_for_render_free(source_path: str, target_path: str) -> None:
    max_side = int(os.environ.get("VERIDITY_IMAGE_MAX_SIDE", "1600"))

    with Image.open(source_path) as image:
        image = ImageOps.exif_transpose(image).convert("RGB")
        image.thumbnail((max_side, max_side))
        image.save(target_path, "JPEG", quality=92, optimize=True)

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

@app.post("/analyze-image")
async def analyze_image_endpoint(file: UploadFile = File(...)):
    try:
        suffix = os.path.splitext(file.filename or "image.jpg")[1] or ".jpg"

        with tempfile.TemporaryDirectory() as temp_dir:
            raw_image_path = os.path.join(temp_dir, "input" + suffix)
            image_path = os.path.join(temp_dir, "input_analysis.jpg")
            output_dir = os.path.join(temp_dir, "results")

            with open(raw_image_path, "wb") as uploaded_file:
                uploaded_file.write(await file.read())

            prepare_image_for_render_free(raw_image_path, image_path)

            report = run_full_investigation_quiet(image_path, output_dir)

            if report.get("status") != "success":
                return JSONResponse(status_code=500, content=report)

            visual_assets = {}
            for key in ("ela", "noise"):
                filename = report.get("results", {}).get(key, {}).get("image_url")
                if not filename:
                    continue

                asset_path = os.path.join(output_dir, filename)
                if os.path.exists(asset_path):
                    with open(asset_path, "rb") as asset:
                        visual_assets[key] = {
                            "filename": filename,
                            "content_base64": base64.b64encode(asset.read()).decode("ascii"),
                        }

            report["visual_assets"] = visual_assets

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
    analyzed_at: str = Form(None),
    extension: str = Form("pdf"),
    document_metrics_str: str = Form("{}"),
    interpretation: str = Form("")
):
    try:
        classification_map = json.loads(classification_map_str)
        document_metrics = json.loads(document_metrics_str)
        pdf_bytes = await file.read()
        
        # Memanggil utilitas eksternal yang sudah diperbaiki
        annotated_pdf_stream = generate_annotated_pdf(
            pdf_bytes, 
            classification_map, 
            metadata_summary=summary_label, 
            audit_id=audit_id,
            analyzed_at=analyzed_at,
            document_metrics=document_metrics,
            interpretation=interpretation,
            source_extension=extension,
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
