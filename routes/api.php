<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BukuApiController;
use App\Http\Controllers\Api\V1\PeminjamanApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Autentikasi API Pustakawan
    Route::post('/auth/login', [AuthTokenController::class, 'login'])->name('api.v1.auth.login');

    // Rute Publik Katalog Buku
    Route::get('/bukus', [BukuApiController::class, 'index'])->name('api.v1.bukus.index');
    Route::get('/bukus/{id}', [BukuApiController::class, 'show'])->name('api.v1.bukus.show');
    Route::get('/bukus/{id}/referensi-eksternal', [BukuApiController::class, 'referensiEksternal'])->name('api.v1.bukus.referensi');

    // Rute Terproteksi Pustakawan (auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('api.v1.auth.logout');

        // Mutasi Buku
        Route::post('/bukus', [BukuApiController::class, 'store'])->name('api.v1.bukus.store');
        Route::put('/bukus/{id}', [BukuApiController::class, 'update'])->name('api.v1.bukus.update');
        Route::patch('/bukus/{id}', [BukuApiController::class, 'patch'])->name('api.v1.bukus.patch');
        Route::delete('/bukus/{id}', [BukuApiController::class, 'destroy'])->name('api.v1.bukus.destroy');

        // Sirkulasi Peminjaman & Pengembalian Buku
        Route::get('/peminjamans', [PeminjamanApiController::class, 'index'])->name('api.v1.peminjamans.index');
        Route::get('/peminjamans/{id}', [PeminjamanApiController::class, 'show'])->name('api.v1.peminjamans.show');
        Route::post('/peminjamans', [PeminjamanApiController::class, 'store'])->name('api.v1.peminjamans.store');
        Route::patch('/peminjamans/{id}/kembalikan', [PeminjamanApiController::class, 'kembalikan'])->name('api.v1.peminjamans.kembalikan');
    });
});
