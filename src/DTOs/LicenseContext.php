<?php

namespace Cierra\Auth\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class LicenseContext
{
    protected array $data;

    public function __construct(array $contextData)
    {
        $this->data = $contextData;
    }

    public function team(): ?array
    {
        return $this->data['team'] ?? null;
    }

    public function user(): ?array
    {
        return $this->data['user'] ?? null;
    }

    public function licenses(): array
    {
        return $this->data['licenses'] ?? [];
    }

    public function hasApplicationLicense(string $slug): bool
    {
        foreach ($this->licenses() as $license) {
            if (($license['application']['slug'] ?? null) === $slug) {
                return $license['status'] === 'active';
            }
        }

        return false;
    }

    public function hasFeature(string $feature): bool
    {
        foreach ($this->licenses() as $license) {
            if ($license['status'] !== 'active') {
                continue;
            }

            $features = $license['plan']['features'] ?? [];
            if (in_array($feature, $features, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasSeat(string $appSlug): bool
    {
        foreach ($this->licenses() as $license) {
            if (($license['application']['slug'] ?? null) === $appSlug) {
                return ($license['has_seat'] ?? false) && $license['status'] === 'active';
            }
        }

        return false;
    }

    public function expiresAt(string $appSlug): ?CarbonInterface
    {
        foreach ($this->licenses() as $license) {
            if (($license['application']['slug'] ?? null) === $appSlug) {
                $expiresAt = $license['expires_at'] ?? null;

                return $expiresAt ? Carbon::parse($expiresAt) : null;
            }
        }

        return null;
    }

    public function plan(string $appSlug): ?string
    {
        foreach ($this->licenses() as $license) {
            if (($license['application']['slug'] ?? null) === $appSlug) {
                return $license['plan']['slug'] ?? null;
            }
        }

        return null;
    }

    public function seats(string $appSlug): array
    {
        foreach ($this->licenses() as $license) {
            if (($license['application']['slug'] ?? null) === $appSlug) {
                return [
                    'purchased' => $license['seats']['purchased'] ?? 0,
                    'used' => $license['seats']['used'] ?? 0,
                ];
            }
        }

        return ['purchased' => 0, 'used' => 0];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
