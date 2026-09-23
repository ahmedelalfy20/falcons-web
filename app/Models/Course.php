<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use Translatable;

    public const TRACKS = ['trading', 'marketing'];

    protected $fillable = [
        'track', 'level', 'title', 'title_ar', 'subtitle', 'subtitle_ar', 'description', 'description_ar',
        'highlights', 'highlights_ar', 'difficulty', 'image', 'is_active',
    ];

    protected function casts(): array
    {
        return ['highlights' => 'array', 'highlights_ar' => 'array', 'is_active' => 'boolean'];
    }

    public function scopeVisible($q)
    {
        return $q->where('is_active', true)->orderBy('track')->orderBy('level');
    }

    public function hasDetails(): bool
    {
        return filled($this->tr('description')) || filled(array_filter((array) $this->tr('highlights')));
    }
}
