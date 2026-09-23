<?php

namespace App\Enums;

enum RoundStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Running = 'running';
    case Paused = 'paused';
    case Finished = 'finished';

    /** Allowed transitions: DRAFT → READY → RUNNING ⇄ PAUSED → FINISHED */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Ready],
            self::Ready => [self::Running, self::Draft],
            self::Running => [self::Paused, self::Finished],
            self::Paused => [self::Running, self::Finished],
            self::Finished => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return __(ucfirst($this->value));
    }

    public function isLive(): bool
    {
        return $this === self::Running || $this === self::Paused;
    }
}
