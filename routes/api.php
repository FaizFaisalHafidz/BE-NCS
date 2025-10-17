<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\AreaGudangController;
use App\Http\Controllers\Api\KategoriBarangController;
use App\Http\Controllers\Api\BarangController;
// use App\Http\Controllers\Api\OptimizationController;
// use App\Http\Controllers\Api\AnalyticsController;

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
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