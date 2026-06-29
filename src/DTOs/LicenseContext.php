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

    /**
     * Per-application access verdicts resolved centrally by admin.cierra.ai.
     *
     * @return array<int, array<string, mixed>>
     */
    public function applications(): array
    {
        return $this->data['applications'] ?? [];
    }

    /**
     * The central access verdict for a single application slug, if present.
     *
     * @return array<string, mixed>|null
     */
    public function applicationAccess(string $slug): ?array
    {
        foreach ($this->applications() as $application) {
            if (($application['slug'] ?? null) === $slug) {
                return $application;
            }
        }

        return null;
    }

    /**
     * Whether the central authority says this user may open the given app.
     *
     * Returns null when the server did not include a verdict for this slug
     * (e.g. an older admin.cierra.ai), so callers can fall back to the
     * license-based checks below.
     */
    public function canAccess(string $slug): ?bool
    {
        $access = $this->applicationAccess($slug);

        // Treat a missing block AND an explicit null value as "no verdict", so
        // callers fall back to the license/seat checks rather than hard-denying.
        if ($access === null || ($access['can_access'] ?? null) === null) {
            return null;
        }

        return (bool) $access['can_access'];
    }

    /**
     * Machine-readable reason for the access verdict (e.g. "free", "no_seat").
     */
    public function accessReason(string $slug): ?string
    {
        return $this->applicationAccess($slug)['reason'] ?? null;
    }

    /**
     * The single, authoritative access decision shared by the EnforceLicense
     * middleware and the License facade so they can never diverge.
     *
     * Order of precedence:
     *  1. central verdict (free apps pass, licensed apps require license+seat);
     *     a `no_seat` denial is ignored when the app opted out via
     *     $requireActiveSeat = false (preserves v0.4 behaviour);
     *  2. no verdict (older server) → license + optional seat check;
     *  3. configured required features must all be present.
     *
     * @param  array<int, string>  $requiredFeatures
     */
    public function permitsAccess(string $slug, bool $requireActiveSeat = true, array $requiredFeatures = []): bool
    {
        $verdict = $this->canAccess($slug);

        if ($verdict === false) {
            // Honour require_active_seat=false: a seat-only denial must not block
            // apps that have opted out of seat enforcement.
            if (! ($requireActiveSeat === false && $this->accessReason($slug) === 'no_seat')) {
                return false;
            }
        } elseif ($verdict === null) {
            if (! $this->hasApplicationLicense($slug)) {
                return false;
            }

            if ($requireActiveSeat && ! $this->hasSeat($slug)) {
                return false;
            }
        }

        foreach ($requiredFeatures as $feature) {
            if (! $this->hasFeature($feature)) {
                return false;
            }
        }

        return true;
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
