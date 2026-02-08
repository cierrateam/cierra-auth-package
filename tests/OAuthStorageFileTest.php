<?php

use Illuminate\Support\Facades\File;

it('loads OAuth credentials from storage file when enrollment secret is set', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-enrollment-secret',
        'cierra-auth-package.client_id' => 'env-client-id',
        'cierra-auth-package.client_secret' => 'env-client-secret',
    ]);

    $storagePath = storage_path('cierra-auth-oauth.json');
    File::put($storagePath, json_encode([
        'client_id' => 'storage-client-id',
        'client_secret' => 'storage-client-secret',
        'expires_at' => '2025-12-24T00:00:00.000000Z',
    ]));

    // Re-boot the service provider to trigger loading from storage
    $provider = $this->app->getProvider(\Cierra\Auth\AuthServiceProvider::class);
    $provider->boot();

    expect(config('cierra-auth-package.client_id'))->toBe('storage-client-id');
    expect(config('cierra-auth-package.client_secret'))->toBe('storage-client-secret');

    // Clean up
    File::delete($storagePath);
});

it('does not load from storage file when enrollment secret is not set', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => null,
        'cierra-auth-package.client_id' => 'env-client-id',
        'cierra-auth-package.client_secret' => 'env-client-secret',
    ]);

    $storagePath = storage_path('cierra-auth-oauth.json');
    File::put($storagePath, json_encode([
        'client_id' => 'storage-client-id',
        'client_secret' => 'storage-client-secret',
        'expires_at' => '2025-12-24T00:00:00.000000Z',
    ]));

    // Re-boot the service provider
    $provider = $this->app->getProvider(\Cierra\Auth\AuthServiceProvider::class);
    $provider->boot();

    expect(config('cierra-auth-package.client_id'))->toBe('env-client-id');
    expect(config('cierra-auth-package.client_secret'))->toBe('env-client-secret');

    // Clean up
    File::delete($storagePath);
});

it('uses config values when storage file does not exist', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-enrollment-secret',
        'cierra-auth-package.client_id' => 'env-client-id',
        'cierra-auth-package.client_secret' => 'env-client-secret',
    ]);

    $storagePath = storage_path('cierra-auth-oauth.json');
    File::delete($storagePath);

    // Re-boot the service provider
    $provider = $this->app->getProvider(\Cierra\Auth\AuthServiceProvider::class);
    $provider->boot();

    expect(config('cierra-auth-package.client_id'))->toBe('env-client-id');
    expect(config('cierra-auth-package.client_secret'))->toBe('env-client-secret');
});

it('handles invalid JSON in storage file gracefully', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-enrollment-secret',
        'cierra-auth-package.client_id' => 'env-client-id',
        'cierra-auth-package.client_secret' => 'env-client-secret',
    ]);

    $storagePath = storage_path('cierra-auth-oauth.json');
    File::put($storagePath, 'invalid json content');

    // Re-boot the service provider
    $provider = $this->app->getProvider(\Cierra\Auth\AuthServiceProvider::class);
    $provider->boot();

    expect(config('cierra-auth-package.client_id'))->toBe('env-client-id');
    expect(config('cierra-auth-package.client_secret'))->toBe('env-client-secret');

    // Clean up
    File::delete($storagePath);
});
