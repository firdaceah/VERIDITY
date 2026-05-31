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

        return [
            'id' => $this->id,
            'file_name' => $this->image_name,
            'file_url' => $this->s3_path ? asset('storage/'.$this->s3_path) : null,
            'image_url' => $this->s3_path ? asset('storage/'.$this->s3_path) : null,
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
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
