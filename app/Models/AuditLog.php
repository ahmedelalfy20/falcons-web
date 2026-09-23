<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only audit trail. Records cannot be updated or deleted through the
 * application layer — there is intentionally no UI or API for it either.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'actor_name', 'action', 'entity_type', 'entity_id', 'metadata', 'ip_address'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit logs are immutable.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Human readable sentence, e.g. "Admin accepted Registration #152." */
    public function sentence(): string
    {
        $actor = $this->actor_name ?: __('System');
        $entity = $this->entity_type ? __(class_basename($this->entity_type)).($this->entity_id ? ' #'.$this->entity_id : '') : '';
        $meta = $this->metadata ?? [];

        return match ($this->action) {
            'registration.accepted' => __(':actor accepted :entity.', ['actor' => $actor, 'entity' => $entity]),
            'registration.rejected' => __(':actor rejected :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.time_added' => __(':actor added :m minutes to :entity.', ['actor' => $actor, 'm' => round(($meta['seconds'] ?? 0) / 60, 1), 'entity' => $entity]),
            'round.time_removed' => __(':actor removed :m minutes from :entity.', ['actor' => $actor, 'm' => round(($meta['seconds'] ?? 0) / 60, 1), 'entity' => $entity]),
            'round.started' => __(':actor started :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.paused' => __(':actor paused :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.resumed' => __(':actor resumed :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.finished' => __(':actor finished :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.auto_finished' => __(':entity finished automatically when the timer reached zero.', ['entity' => $entity]),
            'round.created' => __(':actor created :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.registration_opened' => __(':actor opened registration for :entity.', ['actor' => $actor, 'entity' => $entity]),
            'leader.approved' => __(':actor approved :entity to join the competition.', ['actor' => $actor, 'entity' => $entity]),
            'leader.rejected' => __(':actor rejected the leader request :entity.', ['actor' => $actor, 'entity' => $entity]),
            'leader.photo_updated' => __(':actor updated the photo of :entity.', ['actor' => $actor, 'entity' => $entity]),
            'round.registration_closed' => __(':actor closed registration for :entity.', ['actor' => $actor, 'entity' => $entity]),
            default => trim($actor.' · '.str_replace(['.', '_'], [' ', ' '], $this->action).' '.$entity),
        };
    }
}
