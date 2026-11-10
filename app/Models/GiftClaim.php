<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftClaim extends Model
{
    protected $fillable = ['leader_id', 'gift_id', 'gift_option_id', 'status', 'delivered_at', 'delivered_by'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Leader::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(GiftOption::class, 'gift_option_id');
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
