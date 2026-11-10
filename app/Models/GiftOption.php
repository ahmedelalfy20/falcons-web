<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftOption extends Model
{
    use Translatable;

    protected $fillable = ['gift_id', 'image', 'label', 'label_ar', 'sort_order'];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    public function imageUrl(bool $thumb = false): string
    {
        return media($thumb ? str_replace('.webp', '-thumb.webp', $this->image) : $this->image);
    }
}
