<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderScore extends Model
{
    protected $fillable = ['round_id', 'leader_id', 'accepted_count', 'pending_count', 'rejected_count', 'score_reached_at'];

    protected function casts(): array
    {
        return ['score_reached_at' => 'datetime'];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Leader::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
