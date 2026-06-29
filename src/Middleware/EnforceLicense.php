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

        // Single, shared access decision (also used by the License facade):
        // prefers the central verdict from admin.cierra.ai — free/public apps
        // pass without a license, licensed apps require a license (+ seat) —
        // and falls back to the license/seat checks against older servers.
        $requireActiveSeat = config('cierra-auth-package.require_active_seat', true);
        $requiredFeatures = config('cierra-auth-package.required_features', []);

        if (! $context->permitsAccess($requiredAppSlug, $requireActiveSeat, $requiredFeatures)) {
            return $this->handleMissingLicense($request);
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
