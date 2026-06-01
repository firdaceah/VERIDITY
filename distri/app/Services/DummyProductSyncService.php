<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DummyProductSyncService
{
    private array $categoryMap = [
        'groceries' => ['name' => 'Sembako & Grocery', 'icon' => 'basket'],
        'beauty' => ['name' => 'Perawatan Diri', 'icon' => 'sparkle'],
        'fragrances' => ['name' => 'Parfum', 'icon' => 'spray'],
        'home-decoration' => ['name' => 'Rumah Tangga', 'icon' => 'home'],
        'kitchen-accessories' => ['name' => 'Dapur', 'icon' => 'utensils'],
    ];

    public function sync(): int
    {
        $synced = 0;

        foreach (array_keys($this->categoryMap) as $dummyCategory) {
            $response = Http::timeout(30)->get("https://dummyjson.com/products/category/{$dummyCategory}", [
                'limit' => 20,
            ]);

            if ($response->failed()) {
                continue;
            }

            $category = $this->ensureCategory($dummyCategory);

            foreach ($response->json('products') ?? [] as $product) {
                $price = max(1000, (int) round(((float) ($product['price'] ?? 1)) * 1600));

                DB::table('products')->updateOrInsert(
                    ['external_id' => 'dummyjson-'.$product['id']],
                    [
                        'category_id' => $category->id,
                        'name' => $product['title'] ?? 'Produk Minimarket',
                        'brand' => $product['brand'] ?? 'Distri',
                        'description' => $product['description'] ?? null,
                        'unit' => 'pcs',
                        'min_qty' => 1,
                        'price' => $price,
                        'stock' => $product['stock'] ?? 25,
                        'rating' => $product['rating'] ?? 4.5,
                        'discount_percentage' => $product['discountPercentage'] ?? 0,
                        'image' => null,
                        'image_url' => $product['thumbnail'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $synced++;
            }
        }

        if ($synced === 0) {
            $synced = $this->seedFallbackProducts();
        }

        return $synced;
    }

    private function ensureCategory(string $slug): object
    {
        $mapped = $this->categoryMap[$slug];

        DB::table('categories')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => $mapped['name'],
                'icon' => $mapped['icon'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return DB::table('categories')->where('slug', $slug)->first();
    }

    private function seedFallbackProducts(): int
    {
        $fallback = [
            ['Sembako & Grocery', 'Beras Premium 5kg', 'Distri Mart', 72000],
            ['Sembako & Grocery', 'Minyak Goreng 2L', 'Distri Mart', 38500],
            ['Sembako & Grocery', 'Gula Pasir 1kg', 'Distri Mart', 17500],
            ['Perawatan Diri', 'Sabun Mandi Fresh', 'Daily Care', 6500],
            ['Rumah Tangga', 'Pembersih Lantai 800ml', 'Home Clean', 21000],
            ['Dapur', 'Sponge Cuci Piring 3pcs', 'Kitchen Pro', 12000],
        ];

        foreach ($fallback as [$categoryName, $name, $brand, $price]) {
            $slug = Str::slug($categoryName);
            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $categoryName, 'icon' => 'basket', 'updated_at' => now(), 'created_at' => now()]
            );
            $category = DB::table('categories')->where('slug', $slug)->first();

            DB::table('products')->updateOrInsert(
                ['name' => $name],
                [
                    'category_id' => $category->id,
                    'brand' => $brand,
                    'description' => 'Produk kebutuhan harian untuk simulasi minimarket Distri.',
                    'unit' => 'pcs',
                    'min_qty' => 1,
                    'price' => $price,
                    'stock' => 40,
                    'rating' => 4.7,
                    'discount_percentage' => 5,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return count($fallback);
    }
}
