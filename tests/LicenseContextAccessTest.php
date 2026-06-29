<?php

use Cierra\Auth\DTOs\LicenseContext;

it('reads the central access verdict for an application', function () {
    $context = new LicenseContext([
        'applications' => [
            ['slug' => 'crm', 'can_access' => true, 'reason' => 'licensed'],
            ['slug' => 'free-app', 'can_access' => true, 'reason' => 'free'],
            ['slug' => 'paid-app', 'can_access' => false, 'reason' => 'no_license'],
        ],
    ]);

    expect($context->canAccess('crm'))->toBeTrue()
        ->and($context->canAccess('free-app'))->toBeTrue()
        ->and($context->canAccess('paid-app'))->toBeFalse()
        ->and($context->accessReason('paid-app'))->toBe('no_license')
        ->and($context->accessReason('free-app'))->toBe('free');
});

it('returns null canAccess when the server provides no verdict for the slug', function () {
    $context = new LicenseContext([
        'applications' => [
            ['slug' => 'crm', 'can_access' => true, 'reason' => 'licensed'],
        ],
    ]);

    expect($context->canAccess('unknown-app'))->toBeNull()
        ->and($context->accessReason('unknown-app'))->toBeNull();
});

it('treats an explicit null can_access value as no verdict (falls back)', function () {
    $context = new LicenseContext([
        'applications' => [
            ['slug' => 'crm', 'can_access' => null, 'reason' => null],
        ],
    ]);

    expect($context->canAccess('crm'))->toBeNull();
});

it('permitsAccess honours require_active_seat=false on a seat-only denial', function () {
    $context = new LicenseContext([
        'applications' => [
            ['slug' => 'crm', 'can_access' => false, 'reason' => 'no_seat'],
        ],
    ]);

    // With seat enforcement on (default), the no_seat verdict blocks.
    expect($context->permitsAccess('crm', true))->toBeFalse()
        // With seat enforcement opted out, access is allowed (v0.4 behaviour).
        ->and($context->permitsAccess('crm', false))->toBeTrue();
});

it('permitsAccess still blocks a non-seat denial even when seats are opt-out', function () {
    $context = new LicenseContext([
        'applications' => [
            ['slug' => 'crm', 'can_access' => false, 'reason' => 'no_license'],
        ],
    ]);

    expect($context->permitsAccess('crm', false))->toBeFalse();
});

it('permitsAccess enforces required features even when the verdict allows access', function () {
    $context = new LicenseContext([
        'applications' => [
            ['slug' => 'crm', 'can_access' => true, 'reason' => 'licensed'],
        ],
        'licenses' => [
            ['application' => ['slug' => 'crm'], 'status' => 'active', 'plan' => ['features' => ['analytics']]],
        ],
    ]);

    expect($context->permitsAccess('crm', true, ['analytics']))->toBeTrue()
        ->and($context->permitsAccess('crm', true, ['missing-feature']))->toBeFalse();
});

it('returns null canAccess when the payload has no applications block (older server)', function () {
    $context = new LicenseContext([
        'licenses' => [
            ['application' => ['slug' => 'crm'], 'status' => 'active'],
        ],
    ]);

    expect($context->canAccess('crm'))->toBeNull()
        ->and($context->applications())->toBe([]);
});
