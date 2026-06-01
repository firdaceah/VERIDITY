<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forensic_analyses', function (Blueprint $table) {
            $table->string('report_pdf_path')->nullable()->after('final_result');
            $table->string('report_status')->default('pending')->after('report_pdf_path');
            $table->text('report_error')->nullable()->after('report_status');
            $table->timestamp('report_generated_at')->nullable()->after('report_error');
        });
    }

    public function down(): void
    {
        Schema::table('forensic_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'report_pdf_path',
                'report_status',
                'report_error',
                'report_generated_at',
            ]);
        });
    }
};
