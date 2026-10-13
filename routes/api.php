<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AcaraController;
use App\Http\Controllers\Api\PesertaWisudaController;
use App\Http\Controllers\Api\TamuUndanganController;
use App\Http\Controllers\Api\LayananScanController;
use App\Http\Controllers\Api\RiwayatScanController;

/*
|--------------------------------------------------------------------------
| Public Routes (Tanpa Auth)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('acara/{slug}', [AcaraController::class, 'showBySlug']);
Route::get('tamu-undangan/code/{code}', [TamuUndanganController::class, 'showByUniqueCode']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Harus Menggunakan Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(
    function () {

        // --- Autentikasi & Profile ---
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        Route::middleware('role:superadmin,admin')->group(
            function () {
                // --- User Management ---
                Route::apiResource('users', UserController::class);

                // --- Acara & Event ---
                Route::apiResource('acaras', AcaraController::class);

                // --- Peserta Wisuda ---
                Route::get('peserta-wisuda-berprestasi', [PesertaWisudaController::class, 'berprestasi']);
                Route::get('peserta-wisuda-by-acara/{acara}', [PesertaWisudaController::class, 'getByIdAcara']);
                Route::get('peserta-wisuda/{prodi}/terbaik', [PesertaWisudaController::class, 'terbaik']);
                Route::patch('peserta-wisuda/{peserta_wisuda}/toggle-terbaik', [PesertaWisudaController::class, 'toggleTerbaik']);
                Route::patch('peserta-wisuda/{peserta_wisuda}/toggle-berprestasi', [PesertaWisudaController::class, 'toggleBerprestasi']);
                Route::post('peserta-wisudas/upload-photo-bulk', [PesertaWisudaController::class, 'uploadBulk']);
                Route::post('peserta-wisuda/{acara}/import-excel', [PesertaWisudaController::class, 'importExcel']);
                Route::apiResource('peserta-wisuda', PesertaWisudaController::class);
                Route::get('peserta-wisuda/export-word/{eventId}', [PesertaWisudaController::class, 'exportWord']);
                Route::patch('peserta-wisuda/{id}/toggle-inspiratif', [PesertaWisudaController::class, 'toggleInspiratif']);

                // --- Tamu Undangan ---
                Route::get('tamu-undangan/acara/{acaraId}', [TamuUndanganController::class, 'getByAcara']);
                Route::post('tamu-undangan/sync-wisudawan/{eventId}', [TamuUndanganController::class, 'syncWisudawan']);
                Route::apiResource('tamu-undangan', TamuUndanganController::class)->except(['index', 'show']);

                // --- Riwayat Scan ---
                Route::get('/riwayat-scan/{eventId}', [RiwayatScanController::class, 'index']);
                Route::delete('/riwayat-scan/{id}', [RiwayatScanController::class, 'destroy']);

                // --- Layanan Scan & Presensi ---
                Route::post('/layanan-scan', [LayananScanController::class, 'store']);
                Route::put('/layanan-scan/{id}/toggle-status', [LayananScanController::class, 'toggleStatus']);
                Route::delete('/layanan-scan/{id}', [LayananScanController::class, 'destroy']);
            }
        );
        // --- Layanan Scan & Presensi ---
        Route::get('/layanan-scan', [LayananScanController::class, 'index']);
        Route::post('/tamu-undangan/presensi', [LayananScanController::class, 'processScan']);
    }
);
