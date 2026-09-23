<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use Translatable;

    protected $fillable = ['collection', 'path', 'thumb_path', 'width', 'height', 'caption', 'caption_ar', 'sort_order'];

    public function scopeCollection($q, string $name)
    {
        return $q->where('collection', $name)->orderBy('sort_order')->orderBy('id');
    }
}
