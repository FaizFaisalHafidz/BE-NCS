<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\AreaGudangController;
use App\Http\Controllers\Api\KategoriBarangController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PenempatanBarangController;
use App\Http\Controllers\Api\LogOptimasiController;
use App\Http\Controllers\Api\LogAktivitasController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\RekomendasiPenempatanController;
use App\Http\Controllers\Api\OptimizationController;
use App\Http\Controllers\Api\AnalyticsController;

// Auth routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::put('update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::put('change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
});

// Protected routes with middleware
Route::middleware(['auth:sanctum', 'auto.log.activity'])->group(function () {
    // Current user info
    Route::get('/me', [AuthController::class, 'me']);
    
    // Log Aktivitas routes
    Route::group(['prefix' => 'log-aktivitas'], function () {
        Route::get('/', [LogAktivitasController::class, 'index']);
        Route::get('/statistics', [LogAktivitasController::class, 'statistics']);
        Route::get('/my-activities', [LogAktivitasController::class, 'myActivities']);
        Route::get('/export', [LogAktivitasController::class, 'export']);
        Route::get('/{id}', [LogAktivitasController::class, 'show']);
        Route::post('/', [LogAktivitasController::class, 'store']);
        Route::delete('/cleanup', [LogAktivitasController::class, 'cleanup']);
    });

    // Reports routes
    Route::group(['prefix' => 'reports'], function () {
        Route::get('/daily', [ReportsController::class, 'dailyReport']);
        Route::get('/weekly', [ReportsController::class, 'weeklyReport']);
        Route::get('/inventory', [ReportsController::class, 'inventoryReport']);
        Route::get('/team-performance', [ReportsController::class, 'teamPerformance']);
        Route::get('/warehouse-capacity', [ReportsController::class, 'warehouseCapacity']);
        Route::get('/optimization', [ReportsController::class, 'optimizationReport']);
        Route::get('/latest', [ReportsController::class, 'latestReports']);
    });
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Users Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('roles', [UserController::class, 'roles']);
        Route::get('stats', [UserController::class, 'stats']);
        Route::get('{user}', [UserController::class, 'show']);
        Route::put('{user}', [UserController::class, 'update']);
        Route::patch('{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::delete('{user}', [UserController::class, 'destroy']);
    });
    
    // Gudang Management
    Route::prefix('gudang')->group(function () {
        Route::get('/', [GudangController::class, 'index']);
        Route::post('/', [GudangController::class, 'store']);
        Route::get('stats', [GudangController::class, 'stats']);
        Route::get('{gudang}', [GudangController::class, 'show']);
        Route::put('{gudang}', [GudangController::class, 'update']);
        Route::patch('{gudang}/toggle-status', [GudangController::class, 'toggleStatus']);
        Route::delete('{gudang}', [GudangController::class, 'destroy']);
        
        // Area Gudang nested routes
        Route::get('{gudang}/area-gudang', [AreaGudangController::class, 'byGudang']);
    });
    
    // Area Gudang Management
    Route::prefix('area-gudang')->group(function () {
        Route::get('/', [AreaGudangController::class, 'index']);
        Route::post('/', [AreaGudangController::class, 'store']);
        Route::get('stats', [AreaGudangController::class, 'stats']);
        Route::get('{areaGudang}', [AreaGudangController::class, 'show']);
        Route::put('{areaGudang}', [AreaGudangController::class, 'update']);
        Route::patch('{areaGudang}/toggle-status', [AreaGudangController::class, 'toggleStatus']);
        Route::delete('{areaGudang}', [AreaGudangController::class, 'destroy']);
    });
    
    // Kategori Barang Management
    Route::prefix('kategori-barang')->group(function () {
        Route::get('/', [KategoriBarangController::class, 'index']);
        Route::post('/', [KategoriBarangController::class, 'store']);
        Route::get('stats', [KategoriBarangController::class, 'stats']);
        Route::get('aktif', [KategoriBarangController::class, 'getActive']);
        Route::get('{kategoriBarang}', [KategoriBarangController::class, 'show']);
        Route::put('{kategoriBarang}', [KategoriBarangController::class, 'update']);
        Route::patch('{kategoriBarang}/toggle-status', [KategoriBarangController::class, 'toggleStatus']);
        Route::delete('{kategoriBarang}', [KategoriBarangController::class, 'destroy']);
    });
    
    // Barang Management
    Route::prefix('barang')->group(function () {
        Route::get('/', [BarangController::class, 'index']);
        Route::post('/', [BarangController::class, 'store']);
        Route::get('stats', [BarangController::class, 'stats']);
        Route::post('scan', [BarangController::class, 'scan']);
        Route::get('search-by-code', [BarangController::class, 'searchByCode']);
        Route::get('{barang}', [BarangController::class, 'show']);
        Route::put('{barang}', [BarangController::class, 'update']);
        Route::patch('{barang}/toggle-status', [BarangController::class, 'toggleStatus']);
        Route::delete('{barang}', [BarangController::class, 'destroy']);
        Route::get('{barang}/qr-code', [BarangController::class, 'generateQrCode']);
        Route::post('{barang}/generate-qr', [BarangController::class, 'generateQrCode']);
    });
    
    // Penempatan Barang Management
    Route::prefix('penempatan-barang')->group(function () {
        Route::get('/', [PenempatanBarangController::class, 'index']);
        Route::post('/', [PenempatanBarangController::class, 'store']);
        Route::get('kadaluarsa', [PenempatanBarangController::class, 'barangKadaluarsa']);
        Route::get('area/{areaId}/kapasitas', [PenempatanBarangController::class, 'checkKapasitasArea']);
        Route::get('barang/{barangId}/histori', [PenempatanBarangController::class, 'historiPenempatan']);
        Route::get('{id}', [PenempatanBarangController::class, 'show']);
        Route::put('{id}', [PenempatanBarangController::class, 'update']);
        Route::delete('{id}', [PenempatanBarangController::class, 'destroy']);
    });
    
    // Log Optimasi Management
    Route::prefix('log-optimasi')->group(function () {
        Route::get('/', [LogOptimasiController::class, 'index']);
        Route::post('/', [LogOptimasiController::class, 'store']);
        Route::get('statistics', [LogOptimasiController::class, 'statistics']);
        Route::get('{logOptimasi}', [LogOptimasiController::class, 'show']);
        Route::put('{logOptimasi}', [LogOptimasiController::class, 'update']);
        Route::delete('{logOptimasi}', [LogOptimasiController::class, 'destroy']);
    });
    
    // Rekomendasi Penempatan Management
    Route::prefix('rekomendasi-penempatan')->group(function () {
        Route::get('/', [RekomendasiPenempatanController::class, 'index']);
        Route::post('/', [RekomendasiPenempatanController::class, 'store']);
        Route::get('statistics', [RekomendasiPenempatanController::class, 'statistics']);
        Route::post('bulk-approve', [RekomendasiPenempatanController::class, 'bulkApprove']);
        Route::get('{rekomendasiPenempatan}', [RekomendasiPenempatanController::class, 'show']);
        Route::patch('{rekomendasiPenempatan}/status', [RekomendasiPenempatanController::class, 'updateStatus']);
        Route::delete('{rekomendasiPenempatan}', [RekomendasiPenempatanController::class, 'destroy']);
    });
    
    // Warehouse Optimization
    Route::prefix('optimization')->group(function () {
        Route::get('algorithms', [OptimizationController::class, 'getAlgorithms']);
        Route::get('warehouse-state', [OptimizationController::class, 'getWarehouseState']);
        Route::post('simulated-annealing', [OptimizationController::class, 'runSimulatedAnnealing']);
        Route::get('{logOptimasiId}/status', [OptimizationController::class, 'getOptimizationStatus']);
        Route::post('{logOptimasiId}/cancel', [OptimizationController::class, 'cancelOptimization']);
        
        // Debug route
        Route::get('debug-python', [OptimizationController::class, 'debugPythonPaths']);
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/', [AnalyticsController::class, 'dashboard']);
        Route::get('utilization', [AnalyticsController::class, 'utilization']);
        Route::get('performance', [AnalyticsController::class, 'performance']);
    });
    
    // Optimization - COMING SOON
    /*
    Route::prefix('optimization')->group(function () {
        Route::post('run', [OptimizationController::class, 'run']);
        Route::get('history', [OptimizationController::class, 'history']);
        Route::get('{id}/recommendations', [OptimizationController::class, 'recommendations']);
        Route::post('{id}/approve', [OptimizationController::class, 'approve']);
    });
    */
    
    // Analytics - COMING SOON
    /*
    Route::prefix('analytics')->group(function () {
        Route::get('dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('utilization', [AnalyticsController::class, 'utilization']);
        Route::get('performance', [AnalyticsController::class, 'performance']);
    });
    */
});