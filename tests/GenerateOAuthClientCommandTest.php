<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

it('displays error when enrollment secret is not set', function () {
    config(['cierra-auth-package.client_enrollment_secret' => null]);

    $this->artisan('cierra-auth:generate-oauth-client', ['name' => 'Test App'])
        ->expectsOutput('CLIENT_ENROLLMENT_SECRET is not set in your .env file.')
        ->assertFailed();
});

it('successfully creates oauth client with auto-generated redirect URI', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-secret',
        'cierra-auth-package.host' => 'https://test.admin.cierra.ai',
        'app.url' => 'https://example.com',
    ]);

    Http::fake([
        'test.admin.cierra.ai/api/oauth/clients/enroll' => Http::response([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'expires_at' => '2025-12-24T00:00:00.000000Z',
        ], 201),
    ]);

    $this->artisan('cierra-auth:generate-oauth-client', [
        'name' => 'Test App',
    ])
        ->expectsOutput('Using redirect URI: https://example.com/cierra-auth/callback')
        ->expectsOutput('✓ OAuth client created successfully!')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://test.admin.cierra.ai/api/oauth/clients/enroll'
            && $request['name'] === 'Test App'
            && $request['redirect'] === ['https://example.com/cierra-auth/callback']
            && $request->hasHeader('X-Client-Enrollment-Secret', 'test-secret');
    });

    // Verify the storage file was created with correct content
    $storagePath = storage_path('cierra-auth-oauth.json');
    expect(File::exists($storagePath))->toBeTrue();

    $data = json_decode(File::get($storagePath), true);
    expect($data['client_id'])->toBe('test-client-id');
    expect($data['client_secret'])->toBe('test-client-secret');
    expect($data['expires_at'])->toBe('2025-12-24T00:00:00.000000Z');
});

it('handles API errors gracefully', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-secret',
        'cierra-auth-package.host' => 'https://test.admin.cierra.ai',
        'app.url' => 'https://example.com',
    ]);

    Http::fake([
        'test.admin.cierra.ai/api/oauth/clients/enroll' => Http::response([
            'error' => 'Unauthorized',
        ], 401),
    ]);

    $this->artisan('cierra-auth:generate-oauth-client', [
        'name' => 'Test App',
    ])
        ->expectsOutput('Failed to create OAuth client.')
        ->assertFailed();
});

it('strips trailing slash from app URL', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-secret',
        'cierra-auth-package.host' => 'https://test.admin.cierra.ai',
        'app.url' => 'https://example.com/',
    ]);

    Http::fake([
        'test.admin.cierra.ai/api/oauth/clients/enroll' => Http::response([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'expires_at' => '2025-12-24T00:00:00.000000Z',
        ], 201),
    ]);

    $this->artisan('cierra-auth:generate-oauth-client', [
        'name' => 'Test App',
    ])
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request['redirect'] === ['https://example.com/cierra-auth/callback'];
    });
});
