<?php

namespace App\Service\PlaceCardEffect;

/**
 * Result of a place card effect execution
 */
class PlaceCardEffectResult
{
    private bool $success;
    private string $message;
    private array $changes = [];
    private ?array $pendingActions = null;

    public function __construct(bool $success, string $message, array $changes = [], ?array $pendingActions = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->changes = $changes;
        $this->pendingActions = $pendingActions;
    }

    public static function success(string $message, array $changes = []): self
    {
        return new self(true, $message, $changes);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public static function requiresAction(string $message, array $pendingActions): self
    {
        return new self(true, $message, [], $pendingActions);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function hasPendingActions(): bool
    {
        return $this->pendingActions !== null;
    }

    public function getPendingActions(): ?array
    {
        return $this->pendingActions;
    }
}
