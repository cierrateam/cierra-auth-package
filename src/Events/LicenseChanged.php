<?php

namespace Cierra\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched by WebhookController after a valid license webhook is received.
 * Consuming apps can hook into this to react to license changes in real time.
 */
class LicenseChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $event,
        public readonly array $data,
    ) {}

    public function licenseId(): ?int
    {
        return $this->data['license_id'] ?? null;
    }

    public function teamId(): ?int
    {
        return $this->data['team_id'] ?? null;
    }

    public function userId(): ?int
    {
        return $this->data['user_id'] ?? null;
    }
}
