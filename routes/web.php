<?php

use Cierra\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// routes with prefix cierra-auth

Route::group(['prefix' => 'cierra-auth', 'middleware' => ['web']], function () {

    Route::group(['middleware' => ['guest']], function () {
        Route::get('/login', [AuthController::class, 'login'])->name('cierra-auth.login');
        Route::get('/callback', [AuthController::class, 'callback'])->name('cierra-auth.callback');
    });

    Route::any('/logout', [AuthController::class, 'logout'])->name('cierra-auth.logout');
});
