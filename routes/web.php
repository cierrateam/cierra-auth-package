<?php

use Cierra\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


//routes with prefix cierra-auth

Route::group(['prefix' => 'cierra-auth', 'middleware' => ['guest']], function () {
    Route::get('/login', [AuthController::class, 'login'])->name('cierra-auth.login');
    Route::get('/callback', [AuthController::class, 'callback'])->name('cierra-auth.callback');
});