<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Translatable;

    protected $fillable = ['name', 'name_ar', 'logo', 'url', 'on_light', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['on_light' => 'boolean', 'is_active' => 'boolean'];
    }

    public function scopeVisible($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
