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
        return [
            'id' => $this->id,
            'image_url' => asset('storage/' . $this->s3_path),
            'status' => $this->final_result['summary_label'],
            'color' => $this->final_result['summary_color'],
            'score' => $this->final_result['full_report']['final_score'],
            'is_ai' => $this->is_deepfake,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
