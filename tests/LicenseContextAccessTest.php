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

it('returns null canAccess when the payload has no applications block (older server)', function () {
    $context = new LicenseContext([
        'licenses' => [
            ['application' => ['slug' => 'crm'], 'status' => 'active'],
        ],
    ]);

    expect($context->canAccess('crm'))->toBeNull()
        ->and($context->applications())->toBe([]);
});
