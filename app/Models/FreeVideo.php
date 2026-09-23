<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class FreeVideo extends Model
{
    use Translatable;

    protected $fillable = ['title', 'title_ar', 'description', 'description_ar', 'duration', 'difficulty', 'video_path', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
