<?php

use Illuminate\Support\Facades\DB;
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
        Schema::table('forensic_analyses', function (Blueprint $table) {
            DB::statement('ALTER TABLE forensic_analyses DROP CONSTRAINT IF EXISTS forensic_analyses_final_result_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forensic_analyses', function (Blueprint $table) {
            //
        });
    }
};
