<?php

namespace App\Services;

use App\Models\ForensicAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Throwable;

class AuditReportService
{
    private const REPORT_VERSION = 5;

    private function evidenceStorage(): EvidenceStorage
    {
        return app(EvidenceStorage::class);
    }

    public function ensureReport(ForensicAnalysis $analysis, ?string $language = null): ?string
    {
        $language = $this->normalizeLanguage($language ?? $this->analysisLanguage($analysis));
        $reportPath = $this->reportPathForLanguage($analysis, $language);

        if ($reportPath && $this->evidenceStorage()->exists($reportPath)) {
            return $reportPath;
        }

        try {
            return $this->generateAndStore($analysis, $language);
        } catch (Throwable $exception) {
            $analysis->forceFill([
                'report_status' => 'failed',
                'report_error' => $exception->getMessage(),
            ])->save();

            return null;
        }
    }

    public function generateAndStore(ForensicAnalysis $analysis, ?string $language = null): string
    {
        $language = $this->normalizeLanguage($language ?? $this->analysisLanguage($analysis));
        $extension = strtolower(pathinfo((string) ($analysis->image_name ?? $analysis->s3_path), PATHINFO_EXTENSION));
        $content = in_array($extension, ['pdf', 'docx'], true)
            ? $this->generateAnnotatedDocumentReport($analysis, $language)
            : $this->generateSummaryReport($analysis, $extension, $language);

        $path = 'reports/'.$analysis->user_id.'/REPORT_VERIDITY_VRD-'.$analysis->id.'_'.$language.'.pdf';
        $this->evidenceStorage()->put($path, $content);
        $finalResult = $this->finalResult($analysis);
        $finalResult['report_pdf_paths'][$language] = $path;
        $finalResult['report_version'] = self::REPORT_VERSION;

        $analysis->forceFill([
            'report_pdf_path' => $path,
            'report_status' => 'ready',
            'report_version' => self::REPORT_VERSION,
            'report_error' => null,
            'report_generated_at' => now(),
            'final_result' => $finalResult,
        ])->save();

        return $path;
    }

    public function reportFileName(ForensicAnalysis $analysis, ?string $language = null): string
    {
        $language = $this->normalizeLanguage($language ?? $this->analysisLanguage($analysis));

        return 'REPORT_VERIDITY_#VRD-'.$analysis->id.'_'.$language.'.pdf';
    }

    private function generateAnnotatedDocumentReport(ForensicAnalysis $analysis, string $language): string
    {
        $pdfPath = $this->sourceFilePath($analysis);
        $finalResult = $this->finalResult($analysis);
        $classificationMap = $finalResult['full_report']['classification_map'] ?? [];
        $summaryLabel = $finalResult['summary_label'] ?? 'MIXED TEXT';
        $document = $finalResult['full_report']['results']['document'] ?? [];
        $metrics = $document['metrics'] ?? [];
        $interpretation = $document['interpretation'] ?? '';
        $interpretationKey = $document['interpretation_key'] ?? '';
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
                'interpretation_key' => $interpretationKey,
                'language' => $language,
            ]);
        } finally {
            if (is_resource($pdfStream)) {
                fclose($pdfStream);
            }
        }

        if ($response->failed()) {
            throw new \RuntimeException($language === 'id'
                ? 'Gagal terhubung dengan Layanan Analisis Forensik (Python Engine).'
                : 'Failed to connect to the Forensic Analysis Service (Python Engine).');
        }

        return $response->body();
    }

    private function generateSummaryReport(ForensicAnalysis $analysis, string $extension, string $language): string
    {
        $isDocument = in_array($extension, ['pdf', 'docx'], true);

        return Pdf::loadView('user.pdf-report', [
            'analysis' => $analysis,
            'isDocument' => $isDocument,
            'fileExtension' => $extension ?: 'unknown',
            'waktuAnalisis' => $this->analyzedAt($analysis),
            'generatedAt' => now('Asia/Jakarta')->format('d M Y, H:i').' WIB',
            'language' => $language,
        ])->setPaper('a4')->output();
    }

    private function sourceFilePath(ForensicAnalysis $analysis): string
    {
        if ($analysis->s3_path && $this->evidenceStorage()->exists($analysis->s3_path)) {
            return $this->evidenceStorage()->temporaryLocalPath($analysis->s3_path);
        }

        $fallback = storage_path('app/public/uploads'.DIRECTORY_SEPARATOR.($analysis->image_name ?? ''));

        if (file_exists($fallback)) {
            return $fallback;
        }

        throw new \RuntimeException($this->analysisLanguage($analysis) === 'id'
            ? 'Berkas fisik dokumen pemeriksaan belum tersedia di server local storage.'
            : 'The physical source document is not available in local server storage.');
    }

    private function finalResult(ForensicAnalysis $analysis): array
    {
        $finalResult = $analysis->final_result;

        if (is_string($finalResult)) {
            return json_decode($finalResult, true) ?: [];
        }

        return is_array($finalResult) ? $finalResult : [];
    }

    private function analysisLanguage(ForensicAnalysis $analysis): string
    {
        $requestLanguage = request('language');
        if (in_array($requestLanguage, ['en', 'id'], true)) {
            return $requestLanguage;
        }

        $finalResult = $this->finalResult($analysis);
        $language = $finalResult['language'] ?? $finalResult['full_report']['language'] ?? null;

        return $this->normalizeLanguage($language);
    }

    private function normalizeLanguage(?string $language): string
    {
        return $language === 'id' ? 'id' : 'en';
    }

    private function reportPathForLanguage(ForensicAnalysis $analysis, string $language): ?string
    {
        $finalResult = $this->finalResult($analysis);
        $languagePath = $finalResult['report_pdf_paths'][$language] ?? null;

        if ($languagePath) {
            return $languagePath;
        }

        $legacyLanguage = $this->normalizeLanguage($finalResult['language'] ?? $finalResult['full_report']['language'] ?? null);
        if (
            $legacyLanguage === $language
            && $analysis->report_pdf_path
            && (int) $analysis->report_version >= self::REPORT_VERSION
        ) {
            return $analysis->report_pdf_path;
        }

        return null;
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
