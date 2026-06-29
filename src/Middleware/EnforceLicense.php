<?php

namespace Cierra\Auth\Middleware;

use Cierra\Auth\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceLicense
{
    protected ContextService $contextService;

    public function __construct(ContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $requiredAppSlug = config('cierra-auth-package.required_application_slug');

        if (! $requiredAppSlug) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $context = $this->contextService->forUser($user);

        // Prefer the central access verdict resolved by admin.cierra.ai. This
        // is the single source of truth: free/public apps are allowed without
        // a license, licensed apps require an active license (+ seat).
        $verdict = $context->canAccess($requiredAppSlug);

        if ($verdict === false) {
            return $this->handleMissingLicense($request);
        }

        // $verdict === null → the server returned no per-app verdict (older
        // admin.cierra.ai). Fall back to the license/seat checks for BC.
        if ($verdict === null) {
            if (! $context->hasApplicationLicense($requiredAppSlug)) {
                return $this->handleMissingLicense($request);
            }

            $requireActiveSeat = config('cierra-auth-package.require_active_seat', true);

            if ($requireActiveSeat && ! $context->hasSeat($requiredAppSlug)) {
                return $this->handleMissingLicense($request);
            }
        }

        // Additional feature gating (applies regardless of how access was granted).
        $requiredFeatures = config('cierra-auth-package.required_features', []);

        foreach ($requiredFeatures as $feature) {
            if (! $context->hasFeature($feature)) {
                return $this->handleMissingLicense($request);
            }
        }

        return $next($request);
    }

    protected function handleMissingLicense(Request $request): Response
    {
        $behavior = config('cierra-auth-package.on_license_missing', 'redirect');

        switch ($behavior) {
            case 'redirect':
                $redirectTo = config('cierra-auth-package.license_missing_redirect', '/cierra-auth/no-license');

                return redirect($redirectTo);

            case 'abort':
                abort(402, 'Payment Required: Valid license required');

            default:
                abort(402, 'Payment Required: Valid license required');
        }
    }
}
