<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forensic_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('image_name');
            $table->string('s3_path')->nullable();
            $table->decimal('ela_score', 5, 2)->default(0);
            $table->boolean('is_deepfake')->default(false);
            $table->json('metadata_details')->nullable();
            $table->text('noise_status')->nullable();
            $table->text('final_result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forensic_analyses');
    }
};
