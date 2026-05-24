<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'ORDERS';

    protected $fillable = [
        'order_id_string',
        'user_id',
        'product_id',
        'quantity',
        'total_amount',
        'proof_of_transfer',
        'veridity_status'
    ];

    // Relasi balik ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi balik ke Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
