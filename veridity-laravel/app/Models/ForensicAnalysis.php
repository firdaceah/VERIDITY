<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForensicAnalysis extends Model
{
    use HasFactory;

    protected $table = 'forensic_analyses';

    protected $fillable = [
        'user_id',
        'image_name',
        's3_path',
        'ela_score',
        'is_deepfake',
        'metadata_details',
        'noise_status',
        'final_result',
        'report_pdf_path',
        'report_status',
        'report_version',
        'report_error',
        'report_generated_at',
    ];

    protected $casts = [
        'metadata_details' => 'json',
        'is_deepfake' => 'boolean',
        'ela_score' => 'float',
        'final_result' => 'json',
        'report_version' => 'integer',
        'report_generated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
