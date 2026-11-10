<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gift extends Model
{
    use Translatable;

    protected $fillable = ['name', 'name_ar', 'description', 'description_ar', 'points_required', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'points_required' => 'integer'];
    }

    public function options(): HasMany
    {
        return $this->hasMany(GiftOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(GiftClaim::class);
    }

    /** Active gifts in ladder order (lowest target first). */
    public function scopeLadder($q)
    {
        return $q->where('is_active', true)->orderBy('points_required')->orderBy('id');
    }

    /** Leaders pick one option when the gift offers more than one. */
    public function hasChoice(): bool
    {
        return $this->options->count() > 1;
    }
}
