<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PersetujuanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\DataKaryawanController;
use App\Http\Controllers\ProfileController; // Pastikan controller ini direfactor ke JSON juga nanti

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- PUBLIC ROUTES (Tidak butuh Token) ---
Route::post('/login', [AuthController::class, 'login']);

// Test DB (Opsional, untuk debug)
Route::get('/test-db', function () {
    return response()->json([
        'users_count' => \App\Models\User::count(),
        'leaves' => \App\Models\Leave::all(),
    ]);
});

// --- PROTECTED ROUTES (Butuh Token Bearer) ---
Route::middleware('auth:sanctum')->group(function () {

    // 1. AUTHENTICATION
    Route::get('/me', [AuthController::class, 'me']); // Cek user yang sedang login
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // 3. PROFILE (Opsional, sesuaikan controller agar return JSON)
    Route::get('/profile', [ProfileController::class, 'edit']); // Biasanya di API jadi 'show' atau 'me'
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // 4. PENGAJUAN CUTI (Staff)
    Route::prefix('pengajuan')->group(function () {
        Route::get('/', [PengajuanController::class, 'index']);   // Form data & history
        Route::post('/', [PengajuanController::class, 'store']);  // Submit cuti
        Route::patch('/{id}/cancel', [PengajuanController::class, 'cancel']); // Batalkan
    });

    // 5. PERSETUJUAN (Manager & HRD)
    // Note: Pastikan PersetujuanController sudah direfactor return JSON
    Route::prefix('persetujuan')->group(function () {
        Route::get('/', [PersetujuanController::class, 'index']);
        Route::patch('/{cutiRequest}', [PersetujuanController::class, 'update']); // Approve/Reject
    });

    // 6. LAPORAN (HRD Only - Middleware logic ada di dalam controller)
    Route::prefix('laporan')->group(function () {
        Route::get('/', [LaporanController::class, 'index']);
        Route::get('/export', [LaporanController::class, 'export']); // Return PDF Blob
    });

    // 7. DATA KARYAWAN (HRD Only)
    Route::prefix('data-karyawan')->group(function () {
        // Struktur Organisasi (Tree View)
        Route::get('/struktur', [DataKaryawanController::class, 'structure']);
        
        // CRUD Karyawan
        Route::get('/', [DataKaryawanController::class, 'index']);
        Route::post('/', [DataKaryawanController::class, 'store']);
        Route::put('/{nik}', [DataKaryawanController::class, 'update']); // Pakai PUT/PATCH untuk update
        Route::delete('/{nik}', [DataKaryawanController::class, 'destroy']);
    });

});