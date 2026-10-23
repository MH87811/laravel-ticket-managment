<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::group(
    [
        'prefix' => 'auth',
        'name' => 'auth.'
    ],
    function () {
        Route::post(
            uri: 'register',
            action: [AuthController::class, 'register']
        )->name('register');

        Route::post(
            uri: 'login',
            action: [AuthController::class, 'login']
        )->name('login');

        Route::post(
            uri: 'refresh',
            action: [AuthController::class, 'refresh']
        )->name('refresh');

        Route::middleware('jwt')->group(function () {
            Route::get(
                uri: 'me',
                action: [AuthController::class, 'me']
            )->name('me');
        });
    }
);
