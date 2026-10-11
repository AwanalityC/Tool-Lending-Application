<?php

use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\PenggunaController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\PengembalianController;
use Illuminate\Support\Facades\Route;

// Publik (dibatasi 10 permintaan per menit per IP)
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Wajib login (token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Semua pengguna yang login boleh melihat katalog
    Route::get('/kategori', [KategoriController::class, 'index']);
    Route::get('/kategori/{kategori}', [KategoriController::class, 'show']);
    Route::get('/alat', [AlatController::class, 'index']);
    Route::get('/alat/{alat}', [AlatController::class, 'show']);

    // Detail peminjaman: pemilik atau staf (dicek oleh PeminjamanPolicy di controller)
    Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show']);

    // Khusus peminjam: mengajukan dan melihat riwayat sendiri
    Route::middleware('role:peminjam')->group(function () {
        Route::post('/peminjaman', [PeminjamanController::class, 'store']);
        Route::get('/riwayat-peminjaman', [PeminjamanController::class, 'riwayat']);
    });

    // Admin dan petugas: kelola data dan proses peminjaman
    Route::middleware('role:admin,petugas')->group(function () {
        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::put('/kategori/{kategori}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy']);

        Route::post('/alat', [AlatController::class, 'store']);
        Route::put('/alat/{alat}', [AlatController::class, 'update']);
        Route::delete('/alat/{alat}', [AlatController::class, 'destroy']);

        Route::get('/peminjaman', [PeminjamanController::class, 'index']);
        Route::post('/peminjaman/{peminjaman}/setujui', [PeminjamanController::class, 'setujui']);
        Route::post('/peminjaman/{peminjaman}/tolak', [PeminjamanController::class, 'tolak']);
        Route::post('/peminjaman/{peminjaman}/pengembalian', [PengembalianController::class, 'store']);
    });

    // Khusus admin
    Route::middleware('role:admin')->prefix('pengguna')->group(function () {
        Route::get('/', [PenggunaController::class, 'index']);
        Route::post('/', [PenggunaController::class, 'store']);
    });
});
