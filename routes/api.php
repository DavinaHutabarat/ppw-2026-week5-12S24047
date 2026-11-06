<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Autentikasi API Pustakawan
    Route::post('/auth/login', [AuthTokenController::class, 'login'])->name('api.v1.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('api.v1.auth.logout');
    });
});
