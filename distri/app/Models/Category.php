<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'CATEGORIES';

    protected $fillable = ['name', 'slug', 'icon'];
}
