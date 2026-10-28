<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public const CATEGORIES = [
        'processors' => 'Процессоры',
        'graphics' => 'Видеокарты',
        'building' => 'Сборка ПК',
        'guides' => 'Советы и руководства',
    ];

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'author', 'category', 'status', 'cover_url'];

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Советы и руководства';
    }
}
