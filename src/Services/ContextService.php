<?php

namespace Cierra\Auth\Services;

use Cierra\Auth\DTOs\LicenseContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContextService
{
    public function fetch($user): array
    {
        $token = $user->token ?? null;

        if (! $token) {
            throw new \RuntimeException('User does not have an access token');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->get(config('cierra-auth-package.host').'/api/me/context');

        if (! $response->ok()) {
            throw new \RuntimeException('Failed to fetch context from admin.cierra.ai: '.$response->status());
        }

        return $response->json();
    }

    public function forUser($user): LicenseContext
    {
        $ttl = config('cierra-auth-package.context_cache_ttl', 300);

        try {
            $cacheKey = "cierra-auth:context:user:{$user->id}";

            $data = Cache::remember($cacheKey, $ttl, function () use ($user) {
                return $this->fetch($user);
            });

            return new LicenseContext($data);
        } catch (\Throwable $e) {
            Log::channel(config('logging.cierra-auth', config('logging.default')))->error('Failed to fetch license context', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return new LicenseContext([]);
        }
    }

    public function forCurrentUser(): LicenseContext
    {
        $user = Auth::user();

        if (! $user) {
            return new LicenseContext([]);
        }

        return $this->forUser($user);
    }

    public function flush($user): void
    {
        $cacheKey = "cierra-auth:context:user:{$user->id}";
        Cache::forget($cacheKey);
    }
}
