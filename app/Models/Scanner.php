<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Scanner extends Model
{
    use Translatable;

    protected $fillable = [
        'slug', 'name', 'name_ar', 'tagline', 'tagline_ar', 'summary', 'summary_ar', 'description', 'description_ar',
        'timeframe', 'methodology', 'targets', 'features', 'images', 'accent', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['features' => 'array', 'images' => 'array', 'is_active' => 'boolean'];
    }

    public function scopeVisible($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function coverImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function thumb(?string $path): ?string
    {
        return $path ? media(str_replace('.webp', '-thumb.webp', $path)) : null;
    }

    /** Images normalised for the lightbox gallery component. */
    public function galleryItems(): array
    {
        return collect($this->images ?? [])->map(fn ($p, $i) => [
            'full' => media($p),
            'thumb' => $this->thumb($p),
            'w' => 1600,
            'h' => 900,
            'caption' => $this->name.' — '.($i + 1),
        ])->all();
    }

    /** Features localised: [['title' => .., 'text' => ..], ...] */
    public function localizedFeatures(): array
    {
        $ar = app()->getLocale() === 'ar';

        return collect($this->features ?? [])->map(fn ($f) => [
            'title' => ($ar && ! empty($f['title_ar'])) ? $f['title_ar'] : ($f['title'] ?? ''),
            'text' => ($ar && ! empty($f['text_ar'])) ? $f['text_ar'] : ($f['text'] ?? ''),
        ])->all();
    }
}
