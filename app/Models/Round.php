<?php

namespace App\Models;

use App\Enums\RoundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id', 'number', 'display_number', 'name', 'name_ar', 'status', 'duration_seconds', 'started_at', 'paused_at',
        'ends_at', 'remaining_seconds', 'finished_at', 'registration_enabled',
    ];

    protected function casts(): array
    {
        return [
            'status' => RoundStatus::class,
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'ends_at' => 'datetime',
            'finished_at' => 'datetime',
            'registration_enabled' => 'boolean',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(LeaderScore::class);
    }

    /** The number visitors see (numbering can be restarted from 1). */
    public function shownNumber(): int
    {
        return (int) ($this->display_number ?? $this->number);
    }

    /** Custom name in the current language, else "Round N". */
    public function displayName(): string
    {
        // Arabic falls back to the English name; English shows "Round N" when it has no name of its own.
        $custom = app()->getLocale() === 'ar' ? ($this->name_ar ?: $this->name) : $this->name;

        return $custom ?: __('Round :n', ['n' => $this->shownNumber()]);
    }

    /** Seconds left according to the server clock. */
    public function remainingSeconds(): int
    {
        return match ($this->status) {
            RoundStatus::Running => $this->ends_at ? max(0, (int) ceil(now()->diffInSeconds($this->ends_at, false))) : 0,
            RoundStatus::Paused => (int) ($this->remaining_seconds ?? 0),
            RoundStatus::Draft, RoundStatus::Ready => (int) $this->duration_seconds,
            RoundStatus::Finished => 0,
        };
    }

    public function hasExpired(): bool
    {
        return $this->status === RoundStatus::Running && $this->ends_at !== null && $this->ends_at->lte(now());
    }

    /** Whether registrations are accepted right now (all backend conditions). */
    public function acceptsRegistrations(): bool
    {
        return $this->status === RoundStatus::Running
            && $this->registration_enabled
            && ! $this->hasExpired();
    }
}
