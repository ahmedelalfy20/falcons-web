<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leader extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'active', 'suspended', 'rejected'];

    protected $fillable = ['competition_id', 'user_id', 'name', 'phone', 'phone_normalized', 'email', 'team', 'photo', 'unique_code', 'qr_token', 'status', 'approved_at', 'approved_by'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(LeaderScore::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('Awaiting approval'),
            'active' => __('Active'),
            'suspended' => __('Suspended'),
            'rejected' => __('Rejected'),
            default => $this->status,
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending' => 'badge-pending',
            'active' => 'badge-accepted',
            default => 'badge-rejected',
        };
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Public URL of the profile photo thumbnail (null when none). */
    public function photoUrl(bool $thumb = true): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return media($thumb ? str_replace('.webp', '-thumb.webp', $this->photo) : $this->photo);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }

    public function referralUrl(): string
    {
        return route('register', ['ref' => $this->unique_code]);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
