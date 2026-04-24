<?php

namespace Cierra\Auth\Facades;

use Carbon\CarbonInterface;
use Cierra\Auth\Services\ContextService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool has(string $feature)
 * @method static bool active()
 * @method static CarbonInterface|null expiresAt()
 * @method static string|null plan()
 * @method static array seats()
 *
 * @see ContextService
 */
class License extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cierra-auth.license';
    }

    public static function has(string $feature): bool
    {
        $context = app(ContextService::class)->forCurrentUser();

        return $context->hasFeature($feature);
    }

    public static function active(): bool
    {
        $appSlug = config('cierra-auth-package.required_application_slug');

        if (! $appSlug) {
            return true;
        }

        $context = app(ContextService::class)->forCurrentUser();

        return $context->hasApplicationLicense($appSlug);
    }

    public static function expiresAt(): ?CarbonInterface
    {
        $appSlug = config('cierra-auth-package.required_application_slug');

        if (! $appSlug) {
            return null;
        }

        $context = app(ContextService::class)->forCurrentUser();

        return $context->expiresAt($appSlug);
    }

    public static function plan(): ?string
    {
        $appSlug = config('cierra-auth-package.required_application_slug');

        if (! $appSlug) {
            return null;
        }

        $context = app(ContextService::class)->forCurrentUser();

        return $context->plan($appSlug);
    }

    public static function seats(): array
    {
        $appSlug = config('cierra-auth-package.required_application_slug');

        if (! $appSlug) {
            return ['purchased' => 0, 'used' => 0];
        }

        $context = app(ContextService::class)->forCurrentUser();

        return $context->seats($appSlug);
    }
}
