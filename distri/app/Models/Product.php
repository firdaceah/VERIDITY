<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'PRODUCTS';

    protected $fillable = [
        'name',
        'unit',
        'min_qty',
        'price',
        'image'
    ];

    // Relasi: Satu produk bisa dibeli di banyak order
    public function orders()
    {
        return $this->hasMany(Order::class, 'product_id');
    }
}
