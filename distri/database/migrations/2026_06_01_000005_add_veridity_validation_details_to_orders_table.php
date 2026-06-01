<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'veridity_validation_details')) {
                $table->text('veridity_validation_details')->nullable()->after('veridity_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'veridity_validation_details')) {
                $table->dropColumn('veridity_validation_details');
            }
        });
    }
};
