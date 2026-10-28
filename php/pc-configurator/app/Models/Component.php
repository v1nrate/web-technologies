<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    protected $fillable = ['category', 'brand', 'name', 'price', 'specs', 'power_watts'];
    protected $casts = ['specs' => 'array', 'price' => 'integer', 'power_watts' => 'integer'];
}
