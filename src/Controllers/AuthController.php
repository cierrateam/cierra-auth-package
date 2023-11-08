<?php

namespace Cierra\Auth\Controllers;

use App\Models\User;
use Cierra\Auth\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login()
    {
        return redirect(Auth::getLoginUrl());
    }

    public function callback()
    {

        $code = request()->code;

        if (! $code) {
            return redirect()->route('cierra-auth.login');
        }

        $tokenRes = Http::post(config('cierra-auth-package.host').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('cierra-auth-package.client_id'),
            'client_secret' => config('cierra-auth-package.client_secret'),
            'redirect_uri' => route('cierra-auth.callback'),
            'code' => request()->code,
        ]);

        if (! $tokenRes->ok()) {
            return redirect()->route('cierra-auth.login');
        }

        $tokenRes = $tokenRes->json();

        if (! isset($tokenRes['access_token'])) {
            return redirect()->route('cierra-auth.login');
        }

        $token = $tokenRes['access_token'];

        // dd($token);
        //get user info
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->get(config('cierra-auth-package.host').'/api/user');

        if (! $response->ok()) {
            dd($response->status(), $response->json());
            throw new \Exception('Error getting user info');
        }

        $passportUser = $response->json();

        $userData = [
            'cierra_auth_id' => $passportUser['id'],
            'first_name' => $passportUser['first_name'],
            'last_name' => $passportUser['last_name'],
            'token' => $token,
            'refresh_token' => isset($tokenRes['refresh_token']) ? $tokenRes['refresh_token'] : null,
            'token_expires_in' => isset($tokenRes['expires_in']) ? now()->addSeconds($tokenRes['expires_in']) : null,
            'email_verified_at' => $passportUser['email_verified_at'],
        ];

        if (Schema::hasColumn('users', 'name')) {
            $userData['name'] = $passportUser['first_name'].' '.$passportUser['last_name'];
        }

        //if there is a password field, fill it with random string
        if (Schema::hasColumn('users', 'password')) {
            $userData['password'] = bcrypt(Str::random(16));
        }

        $user = User::updateOrCreate(
            [
                'email' => $passportUser['email'],
            ],
            $userData
        );

        //get current session driver
        $sessionDriver = config('session.driver');
        dd($sessionDriver);
        FacadesAuth::login($user);
        session(['key11' => 'valueee']);

        return redirect(config('cierra-auth-package.redirect_after_login'));
    }
}
