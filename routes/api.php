<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WargaController;
use App\Http\Controllers\PencatatanMeterController;
use App\Http\Controllers\TagihanController;
use Illuminate\Support\Facades\Route;

// ====================================================
// 1. ZONA PUBLIK (Tanpa Token)
// ====================================================
Route::post('/login', [AuthController::class, 'login']);

// ====================================================
// 2. ZONA TERPROTEKSI TOKEN SANCTUM
// ====================================================
Route::middleware('auth:sanctum')->group(function () {

    // Endpoint Identitas & Logout (Semua Role Terautentikasi)
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // A. ZONA KHUSUS PETUGAS LAPANGAN (role:petugas)
    Route::middleware('role:petugas')->group(function () {
        Route::get('/warga', [WargaController::class, 'index']);
        Route::post('/warga', [WargaController::class, 'store']);
        Route::post('/pencatatan-meter', [PencatatanMeterController::class, 'store']);
    });

    // B. ZONA WARGA & PETUGAS (role:petugas,warga)
    Route::middleware('role:petugas,warga')->group(function () {
        Route::get('/tagihan', [TagihanController::class, 'index']);
        Route::patch('/tagihan/{id}/bayar', [TagihanController::class, 'bayar']);
    });
});


