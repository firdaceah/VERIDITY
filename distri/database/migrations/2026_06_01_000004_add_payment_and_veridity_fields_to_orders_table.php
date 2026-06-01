<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('proof_of_transfer');
            }
            if (! Schema::hasColumn('orders', 'payment_channel')) {
                $table->string('payment_channel', 80)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 50)->default('pending')->after('payment_channel');
            }
            if (! Schema::hasColumn('orders', 'payment_instruction')) {
                $table->text('payment_instruction')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('orders', 'veridity_audit_id')) {
                $table->unsignedBigInteger('veridity_audit_id')->nullable()->after('veridity_status');
            }
            if (! Schema::hasColumn('orders', 'veridity_score')) {
                $table->decimal('veridity_score', 7, 2)->nullable()->after('veridity_audit_id');
            }
            if (! Schema::hasColumn('orders', 'veridity_message')) {
                $table->text('veridity_message')->nullable()->after('veridity_score');
            }
            if (! Schema::hasColumn('orders', 'veridity_checked_at')) {
                $table->timestamp('veridity_checked_at')->nullable()->after('veridity_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'payment_method',
                'payment_channel',
                'payment_status',
                'payment_instruction',
                'veridity_audit_id',
                'veridity_score',
                'veridity_message',
                'veridity_checked_at',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
