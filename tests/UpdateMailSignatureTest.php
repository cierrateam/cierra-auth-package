<?php

use Cierra\Auth\Auth;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['cierra-auth-package.host' => 'https://admin.test']);
});

it('PUTs the signature to the central profile with the user bearer token', function () {
    Http::fake([
        'admin.test/api/me/mail-signature' => Http::response([
            'user' => ['mail_signature' => '<sig>', 'position' => 'CTO'],
        ], 200),
    ]);

    $this->be(new GenericUser(['id' => 1, 'token' => 'user-token']));

    $result = Auth::updateMailSignature('<sig>', 'CTO');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://admin.test/api/me/mail-signature'
            && $request->method() === 'PUT'
            && $request->hasHeader('Authorization', 'Bearer user-token')
            && $request['mail_signature'] === '<sig>'
            && $request['position'] === 'CTO';
    });
});

it('omits position from the payload when not provided', function () {
    Http::fake([
        'admin.test/api/me/mail-signature' => Http::response([], 200),
    ]);

    $this->be(new GenericUser(['id' => 1, 'token' => 'user-token']));

    Auth::updateMailSignature('<sig>');

    Http::assertSent(function ($request) {
        return $request['mail_signature'] === '<sig>'
            && ! array_key_exists('position', $request->data());
    });
});

it('returns false without making a request when the user has no token', function () {
    Http::fake();

    $this->be(new GenericUser(['id' => 1]));

    expect(Auth::updateMailSignature('<sig>'))->toBeFalse();

    Http::assertNothingSent();
});

it('returns false when the central profile update fails', function () {
    Http::fake([
        'admin.test/api/me/mail-signature' => Http::response('nope', 500),
    ]);

    $this->be(new GenericUser(['id' => 1, 'token' => 'user-token']));

    expect(Auth::updateMailSignature('<sig>'))->toBeFalse();
});
