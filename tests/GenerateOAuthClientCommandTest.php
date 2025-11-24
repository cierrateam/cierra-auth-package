<?php

use Illuminate\Support\Facades\Http;

it('displays error when enrollment secret is not set', function () {
    config(['cierra-auth-package.client_enrollment_secret' => null]);

    $this->artisan('cierra-auth:generate-oauth-client', ['name' => 'Test App'])
        ->expectsOutput('CLIENT_ENROLLMENT_SECRET is not set in your .env file.')
        ->assertFailed();
});

it('successfully creates oauth client with redirect URIs', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-secret',
        'cierra-auth-package.host' => 'https://test.admin.cierra.ai',
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
        '--redirect' => ['https://example.com/callback'],
    ])
        ->expectsOutput('✓ OAuth client created successfully!')
        ->expectsQuestion('Would you like to update your .env file with these credentials?', false)
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://test.admin.cierra.ai/api/oauth/clients/enroll'
            && $request['name'] === 'Test App'
            && $request['redirect'] === ['https://example.com/callback']
            && $request->hasHeader('Authorization', 'X-Client-Enrollment-Secret: test-secret');
    });
});

it('handles API errors gracefully', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-secret',
        'cierra-auth-package.host' => 'https://test.admin.cierra.ai',
    ]);

    Http::fake([
        'test.admin.cierra.ai/api/oauth/clients/enroll' => Http::response([
            'error' => 'Unauthorized',
        ], 401),
    ]);

    $this->artisan('cierra-auth:generate-oauth-client', [
        'name' => 'Test App',
        '--redirect' => ['https://example.com/callback'],
    ])
        ->expectsOutput('Failed to create OAuth client.')
        ->assertFailed();
});

it('accepts multiple redirect URIs', function () {
    config([
        'cierra-auth-package.client_enrollment_secret' => 'test-secret',
        'cierra-auth-package.host' => 'https://test.admin.cierra.ai',
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
        '--redirect' => [
            'https://example.com/callback',
            'https://staging.example.com/callback',
        ],
    ])
        ->expectsQuestion('Would you like to update your .env file with these credentials?', false)
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request['redirect'] === [
            'https://example.com/callback',
            'https://staging.example.com/callback',
        ];
    });
});

