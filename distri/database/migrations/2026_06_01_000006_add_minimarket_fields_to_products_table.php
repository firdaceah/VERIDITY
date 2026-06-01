<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('icon', 60)->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'external_id')) {
                $table->string('external_id', 80)->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 120)->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('brand');
            }
            if (! Schema::hasColumn('products', 'stock')) {
                $table->integer('stock')->default(50)->after('price');
            }
            if (! Schema::hasColumn('products', 'rating')) {
                $table->decimal('rating', 3, 2)->default(4.50)->after('stock');
            }
            if (! Schema::hasColumn('products', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0)->after('rating');
            }
            if (! Schema::hasColumn('products', 'image_url')) {
                $table->text('image_url')->nullable()->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (['image_url', 'discount_percentage', 'rating', 'stock', 'description', 'brand', 'external_id'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('categories');
    }
};
