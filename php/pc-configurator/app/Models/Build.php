<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Build extends Model
{
    protected $fillable = ['title', 'items', 'total_price', 'warnings', 'user_id'];
    protected $casts = ['items' => 'array', 'warnings' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
}
