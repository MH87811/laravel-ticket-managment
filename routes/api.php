<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post(
    uri: 'register',
    action: [AuthController::class, 'register']
)->name('register');

Route::post(
    uri: 'login',
    action: [AuthController::class, 'login']
)->name('login');

Route::middleware('jwt')->group(function () {
    Route::get(
        uri: 'me',
        action: [AuthController::class, 'me']
    );
});
