<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use Translatable;

    protected $fillable = ['slug', 'name', 'name_ar', 'role', 'role_ar', 'city', 'city_ar', 'bio', 'bio_ar', 'image', 'gallery', 'experience_years', 'company_id', 'links', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['links' => 'array', 'gallery' => 'array', 'is_active' => 'boolean'];
    }

    public function scopeVisible($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Name without an honorific prefix (used in "Join through …"). */
    public function plainName(): string
    {
        return (string) \Illuminate\Support\Str::of($this->tr('name'))->replace(['Coach ', 'الكوتش '], '')->trim();
    }

    /** Events gallery normalised for the lightbox component. */
    public function galleryItems(): array
    {
        return collect($this->gallery ?? [])->values()->map(function ($p, $i) {
            $thumb = str_replace('.webp', '-thumb.webp', $p);
            $size = @getimagesize(public_path($p)) ?: [1600, 1066];

            return [
                'full' => media($p),
                'thumb' => media(file_exists(public_path($thumb)) ? $thumb : $p),
                'w' => $size[0], 'h' => $size[1],
                'caption' => __(':name — event photo :n', ['name' => $this->plainName(), 'n' => $i + 1]),
            ];
        })->all();
    }

    public function hasBio(): bool
    {
        return filled($this->tr('bio'));
    }
}
