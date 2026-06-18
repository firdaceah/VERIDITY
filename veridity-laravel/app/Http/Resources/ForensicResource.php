<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForensicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $finalResult = is_array($this->final_result) ? $this->final_result : [];
        $fullReport = is_array($finalResult['full_report'] ?? null) ? $finalResult['full_report'] : [];
        $extension = strtolower(pathinfo((string) $this->image_name, PATHINFO_EXTENSION));
        $isDocument = in_array($extension, ['pdf', 'docx'], true);
        $results = is_array($fullReport['results'] ?? null) ? $fullReport['results'] : [];

        return [
            'id' => $this->id,
            'file_name' => $this->image_name,
            'file_extension' => $extension,
            'file_type' => $isDocument ? 'document' : 'image',
            'storage_path' => $this->s3_path,
            'file_url' => $this->s3_path ? route('files.public', ['path' => $this->s3_path]) : null,
            'image_url' => $this->s3_path ? route('files.public', ['path' => $this->s3_path]) : null,
            'ela_image_url' => isset($results['ela']['image_url'])
                ? route('files.public', ['path' => 'results/'.$this->user_id.'/'.$results['ela']['image_url']])
                : null,
            'noise_image_url' => isset($results['noise']['image_url'])
                ? route('files.public', ['path' => 'results/'.$this->user_id.'/'.$results['noise']['image_url']])
                : null,
            'summary_label' => $finalResult['summary_label'] ?? 'UNKNOWN',
            'summary_color' => $finalResult['summary_color'] ?? 'warning',
            'status' => $finalResult['summary_label'] ?? 'UNKNOWN',
            'color' => $finalResult['summary_color'] ?? 'warning',
            'final_score' => $fullReport['final_score'] ?? 0,
            'score' => $fullReport['final_score'] ?? 0,
            'is_ai' => $this->is_deepfake,
            'ela_score' => $this->ela_score,
            'noise_status' => $this->noise_status,
            'metadata_details' => $this->metadata_details,
            'final_result' => $finalResult,
            'report_status' => $this->report_status,
            'report_version' => $this->report_version,
            'report_generated_at' => optional($this->report_generated_at)->toISOString(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
