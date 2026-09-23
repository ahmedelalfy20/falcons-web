<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Enums\RoundStatus;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    use HasFactory, Translatable;

    public const DEFAULT_CONFIG = [
        // 'round' = a participant may join once per round; 'competition' = once per competition
        'duplicate_scope' => 'competition',
        'require_email' => true,
        // Participants must upload a screenshot of their payment transfer.
        'require_transfer_proof' => true,
        'collect_city' => true,
        'leader_self_registration' => true,
        // false = new leaders wait for admin approval before their QR works / they appear on the board
        'leader_auto_approve' => false,
        'public_leaderboard' => true,
        'default_round_minutes' => 1440,
    ];

    protected $fillable = ['name', 'name_ar', 'status', 'configuration'];

    protected function casts(): array
    {
        return [
            'status' => CompetitionStatus::class,
            'configuration' => 'array',
        ];
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('number');
    }

    public function leaders(): HasMany
    {
        return $this->hasMany(Leader::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function config(string $key): mixed
    {
        return ($this->configuration ?? [])[$key] ?? self::DEFAULT_CONFIG[$key] ?? null;
    }

    public function fullConfig(): array
    {
        return array_merge(self::DEFAULT_CONFIG, $this->configuration ?? []);
    }

    public function isActive(): bool
    {
        return $this->status === CompetitionStatus::Active;
    }

    /** The round currently in play (not finished), or the most recent one. */
    public function currentRound(): ?Round
    {
        return $this->rounds()->where('status', '!=', RoundStatus::Finished->value)->orderByDesc('number')->first()
            ?? $this->rounds()->reorder()->orderByDesc('number')->first();
    }

    public static function active(): ?self
    {
        return static::where('status', CompetitionStatus::Active->value)->latest('id')->first();
    }
}
