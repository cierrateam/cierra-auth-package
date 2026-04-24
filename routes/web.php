<?php

use Cierra\Auth\Controllers\AuthController;
use Cierra\Auth\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Main web-session routes (OAuth callback, login, logout)
Route::group(['prefix' => 'cierra-auth', 'middleware' => ['web']], function () {

    Route::group(['middleware' => ['guest']], function () {
        Route::get('/login', [AuthController::class, 'login'])->name('cierra-auth.login');
        Route::get('/callback', [AuthController::class, 'callback'])->name('cierra-auth.callback');
    });

    Route::any('/logout', [AuthController::class, 'logout'])->name('cierra-auth.logout');

    Route::get('/no-license', function () {
        return view('cierra-auth-package::no-license');
    })->name('cierra-auth.no-license');
});

// Webhook receiver — stateless, no web middleware, no CSRF
Route::post('/cierra-auth/webhook', WebhookController::class)
    ->name('cierra-auth.webhook');
