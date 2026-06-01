<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'PRODUCTS';

    protected $fillable = [
        'category_id',
        'external_id',
        'name',
        'brand',
        'description',
        'unit',
        'min_qty',
        'price',
        'stock',
        'rating',
        'discount_percentage',
        'image',
        'image_url',
    ];

    // Relasi: Satu produk bisa dibeli di banyak order
    public function orders()
    {
        return $this->hasMany(Order::class, 'product_id');
    }
}
