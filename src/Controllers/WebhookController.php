<?php

namespace Cierra\Auth\Controllers;

use Cierra\Auth\Events\LicenseChanged;
use Cierra\Auth\Services\ContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Receives signed license webhooks from admin.cierra.ai.
 *
 * Validates X-Cierra-Signature (HMAC-SHA256 of raw body w/ webhook_secret),
 * flushes context cache for affected users, and dispatches a Laravel event
 * so consuming apps can hook in.
 */
class WebhookController
{
    public function __construct(private readonly ContextService $contextService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('cierra-auth-package.webhook_secret');

        if (! $secret) {
            Log::warning('[cierra-auth] webhook received but no webhook_secret configured');

            return response()->json(['error' => 'Webhooks not configured'], 503);
        }

        $signature = (string) $request->header('X-Cierra-Signature', '');
        $body = $request->getContent();

        if (! $this->verifySignature($signature, $body, $secret)) {
            Log::warning('[cierra-auth] webhook signature mismatch', [
                'ip' => $request->ip(),
                'event' => $request->header('X-Cierra-Event'),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($body, true) ?: [];
        $event = $payload['event'] ?? ($request->header('X-Cierra-Event') ?: 'unknown');
        $data = $payload['data'] ?? [];

        if (config('cierra-auth-package.log_webhook_payloads', false)) {
            Log::channel(config('logging.cierra-auth', config('logging.default')))
                ->info('[cierra-auth] webhook received', [
                    'event' => $event,
                    'data' => $data,
                ]);
        }

        // Flush context cache for affected team/user
        $this->flushCacheFor($data);

        // Let the host app react
        event(new LicenseChanged($event, $data));

        return response()->json(['ok' => true, 'handled' => $event]);
    }

    private function verifySignature(string $header, string $body, string $secret): bool
    {
        $provided = str_starts_with($header, 'sha256=')
            ? substr($header, 7)
            : $header;

        $computed = hash_hmac('sha256', $body, $secret);

        return hash_equals($computed, $provided);
    }

    private function flushCacheFor(array $data): void
    {
        $userId = $data['user_id'] ?? null;
        $teamId = $data['team_id'] ?? null;

        if ($userId) {
            Cache::forget("cierra-auth:context:user:{$userId}");
        }

        // Broad flush for team-level events: iterate common prefix if supported.
        // Apps can also hook into LicenseChanged event for custom invalidation.
        if ($teamId && method_exists(Cache::getStore(), 'tags')) {
            try {
                Cache::tags(['cierra-auth', "team:{$teamId}"])->flush();
            } catch (\Throwable $e) {
                // store doesn't actually support tags
            }
        }
    }
}
