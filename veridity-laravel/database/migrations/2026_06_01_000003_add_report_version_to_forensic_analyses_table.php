<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forensic_analyses', function (Blueprint $table) {
            if (! Schema::hasColumn('forensic_analyses', 'report_version')) {
                $table->unsignedSmallInteger('report_version')->default(0)->after('report_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forensic_analyses', function (Blueprint $table) {
            if (Schema::hasColumn('forensic_analyses', 'report_version')) {
                $table->dropColumn('report_version');
            }
        });
    }
};
