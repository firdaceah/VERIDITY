<?php

namespace App\Services;

use App\Models\ForensicAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditReportService
{
    private const REPORT_VERSION = 3;

    public function ensureReport(ForensicAnalysis $analysis): ?string
    {
        if (
            $analysis->report_pdf_path
            && (int) $analysis->report_version >= self::REPORT_VERSION
            && Storage::disk('public')->exists($analysis->report_pdf_path)
        ) {
            return $analysis->report_pdf_path;
        }

        try {
            return $this->generateAndStore($analysis);
        } catch (Throwable $exception) {
            $analysis->forceFill([
                'report_status' => 'failed',
                'report_error' => $exception->getMessage(),
            ])->save();

            return null;
        }
    }

    public function generateAndStore(ForensicAnalysis $analysis): string
    {
        $extension = strtolower(pathinfo((string) ($analysis->image_name ?? $analysis->s3_path), PATHINFO_EXTENSION));
        $content = in_array($extension, ['pdf', 'docx'], true)
            ? $this->generateAnnotatedDocumentReport($analysis)
            : $this->generateSummaryReport($analysis, $extension);

        $path = 'reports/'.$analysis->user_id.'/REPORT_VERIDITY_VRD-'.$analysis->id.'.pdf';
        Storage::disk('public')->put($path, $content);

        $analysis->forceFill([
            'report_pdf_path' => $path,
            'report_status' => 'ready',
            'report_version' => self::REPORT_VERSION,
            'report_error' => null,
            'report_generated_at' => now(),
        ])->save();

        return $path;
    }

    public function reportFileName(ForensicAnalysis $analysis): string
    {
        return 'REPORT_VERIDITY_#VRD-'.$analysis->id.'.pdf';
    }

    private function generateAnnotatedDocumentReport(ForensicAnalysis $analysis): string
    {
        $pdfPath = $this->sourceFilePath($analysis);
        $finalResult = $this->finalResult($analysis);
        $classificationMap = $finalResult['full_report']['classification_map'] ?? [];
        $summaryLabel = $finalResult['summary_label'] ?? 'MIXED TEXT';
        $document = $finalResult['full_report']['results']['document'] ?? [];
        $metrics = $document['metrics'] ?? [];
        $interpretation = $document['interpretation'] ?? '';
        $extension = strtolower(pathinfo((string) ($analysis->image_name ?? $analysis->s3_path), PATHINFO_EXTENSION));
        $pdfStream = fopen($pdfPath, 'r');

        try {
            $response = Http::timeout(120)->attach(
                'file',
                $pdfStream,
                'document.pdf'
            )->post($this->pythonEngineUrl().'/generate-pdf-report', [
                'classification_map_str' => json_encode($classificationMap),
                'summary_label' => $summaryLabel,
                'audit_id' => $analysis->id,
                'analyzed_at' => $this->analyzedAt($analysis),
                'extension' => $extension,
                'document_metrics_str' => json_encode($metrics),
                'interpretation' => $interpretation,
            ]);
        } finally {
            if (is_resource($pdfStream)) {
                fclose($pdfStream);
            }
        }

        if ($response->failed()) {
            throw new \RuntimeException('Gagal terhubung dengan Layanan Analisis Forensik (Python Engine).');
        }

        return $response->body();
    }

    private function generateSummaryReport(ForensicAnalysis $analysis, string $extension): string
    {
        $isDocument = in_array($extension, ['pdf', 'docx'], true);

        return Pdf::loadView('user.pdf-report', [
            'analysis' => $analysis,
            'isDocument' => $isDocument,
            'fileExtension' => $extension ?: 'unknown',
            'waktuAnalisis' => $this->analyzedAt($analysis),
            'generatedAt' => now('Asia/Jakarta')->format('d M Y, H:i').' WIB',
        ])->setPaper('a4')->output();
    }

    private function sourceFilePath(ForensicAnalysis $analysis): string
    {
        if ($analysis->s3_path && Storage::disk('public')->exists($analysis->s3_path)) {
            return Storage::disk('public')->path($analysis->s3_path);
        }

        $fallback = storage_path('app/public/uploads'.DIRECTORY_SEPARATOR.($analysis->image_name ?? ''));

        if (file_exists($fallback)) {
            return $fallback;
        }

        throw new \RuntimeException('Berkas fisik dokumen pemeriksaan belum tersedia di server local storage.');
    }

    private function finalResult(ForensicAnalysis $analysis): array
    {
        $finalResult = $analysis->final_result;

        if (is_string($finalResult)) {
            return json_decode($finalResult, true) ?: [];
        }

        return is_array($finalResult) ? $finalResult : [];
    }

    private function analyzedAt(ForensicAnalysis $analysis): string
    {
        return ($analysis->created_at ?: now())
            ->setTimezone('Asia/Jakarta')
            ->format('d M Y, H:i').' WIB';
    }

    private function pythonEngineUrl(): string
    {
        return rtrim((string) config('services.veridity.python_engine_url', 'http://127.0.0.1:8001'), '/');
    }
}
