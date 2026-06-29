<?php

use Cierra\Auth\DTOs\LicenseContext;
use Cierra\Auth\Services\ContextService;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Route;

/**
 * Bind a ContextService that always returns the given context payload,
 * so the middleware never makes a real HTTP call.
 */
function fakeContext(array $payload): void
{
    app()->instance(ContextService::class, new class($payload) extends ContextService
    {
        public function __construct(private array $payload) {}

        public function forUser($user): LicenseContext
        {
            return new LicenseContext($this->payload);
        }
    });
}

function protectedRoute(): void
{
    Route::middleware('license')->get('/protected', fn () => 'ok');
}

beforeEach(function () {
    config([
        'cierra-auth-package.required_application_slug' => 'crm',
        'cierra-auth-package.on_license_missing' => 'redirect',
        'cierra-auth-package.license_missing_redirect' => '/cierra-auth/no-license',
        'cierra-auth-package.required_features' => [],
    ]);

    $this->be(new GenericUser(['id' => 1, 'token' => 'fake-token']));
});

it('allows access when the central verdict says can_access (free app)', function () {
    fakeContext(['applications' => [
        ['slug' => 'crm', 'can_access' => true, 'reason' => 'free'],
    ]]);
    protectedRoute();

    $this->get('/protected')->assertOk()->assertSee('ok');
});

it('denies access when the central verdict says cannot access', function () {
    fakeContext(['applications' => [
        ['slug' => 'crm', 'can_access' => false, 'reason' => 'no_license'],
    ]]);
    protectedRoute();

    $this->get('/protected')->assertRedirect('/cierra-auth/no-license');
});

it('falls back to license check when the server returns no verdict', function () {
    fakeContext(['licenses' => [
        [
            'application' => ['slug' => 'crm'],
            'status' => 'active',
            'has_seat' => true,
        ],
    ]]);
    config(['cierra-auth-package.require_active_seat' => true]);
    protectedRoute();

    $this->get('/protected')->assertOk();
});

it('denies via fallback when no verdict and no license', function () {
    fakeContext(['licenses' => []]);
    protectedRoute();

    $this->get('/protected')->assertRedirect('/cierra-auth/no-license');
});

it('passes through when no required_application_slug is configured', function () {
    config(['cierra-auth-package.required_application_slug' => null]);
    fakeContext([]);
    protectedRoute();

    $this->get('/protected')->assertOk();
});
