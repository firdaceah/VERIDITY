<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'ORDER_ITEMS';

    protected $fillable = ['order_id', 'product_id', 'product_name', 'quantity', 'price', 'subtotal'];
}
