<?php

use Cierra\Auth\Auth;
use Cierra\Auth\Services\ContextService;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal Eloquent + Authenticatable user backed by the test `users` table,
 * used to exercise the local-mirror branch of updateMailSignature().
 */
class MirrorUser extends Authenticatable
{
    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];
}

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

it('does not fatal mirroring onto a non-eloquent user even when columns exist', function () {
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('email')->nullable();
        $table->string('position')->nullable();
        $table->longText('mail_signature')->nullable();
    });

    Http::fake(['admin.test/api/me/mail-signature' => Http::response([], 200)]);

    // GenericUser has no forceFill/save — the helper must skip the mirror.
    $this->be(new GenericUser(['id' => 1, 'token' => 'user-token']));

    expect(Auth::updateMailSignature('<sig>', 'CTO'))->toBeTrue();
});

it('mirrors the signature onto the local eloquent user and flushes context', function () {
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('email')->nullable();
        $table->string('token')->nullable();
        $table->string('position')->nullable();
        $table->longText('mail_signature')->nullable();
    });

    Http::fake(['admin.test/api/me/mail-signature' => Http::response([], 200)]);

    // Record flush() calls without making a real context fetch.
    $spy = new class extends ContextService
    {
        public $flushedFor = null;

        public function flush($user): void
        {
            $this->flushedFor = $user->id;
        }
    };
    app()->instance(ContextService::class, $spy);

    $user = MirrorUser::create(['email' => 'a@b.c', 'token' => 'user-token']);
    $this->be($user);

    expect(Auth::updateMailSignature('<sig>', 'CTO'))->toBeTrue();

    $fresh = MirrorUser::find($user->id);
    expect($fresh->mail_signature)->toBe('<sig>')
        ->and($fresh->position)->toBe('CTO')
        ->and($spy->flushedFor)->toBe($user->id);
});
