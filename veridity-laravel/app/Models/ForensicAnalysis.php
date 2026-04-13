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
        'final_result'
    ];

    protected $casts = [
        'metadata_details' => 'json',
        'is_deepfake' => 'boolean',
        'ela_score' => 'float',
        'final_result' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
