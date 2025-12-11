<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\KaryawanController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\PelangganController;
use App\Http\Controllers\API\AuthAdminController;
use App\Http\Controllers\API\AuthKaryawanController;
use App\Http\Controllers\API\LayananController;
use App\Http\Controllers\API\TransaksiController;
use App\Http\Controllers\API\CashFlowController;
use App\Http\Controllers\API\PickupSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 📌 AUTH ADMIN
Route::get('/check-admin-exists', [AuthAdminController::class, 'checkAdminExists']);
Route::post('/register-first-admin', [AuthAdminController::class, 'registerFirstAdmin']);
Route::post('/login-admin', [AuthAdminController::class, 'login']);

// 📌 AUTH KARYAWAN
Route::post('/login-karyawan', [AuthKaryawanController::class, 'login']);

// 📌 ADMIN CRUD
Route::get('/admin', [AdminController::class, 'index']);
Route::post('/admin', [AdminController::class, 'store']);
Route::put('/admin/{id}', [AdminController::class, 'update']);
Route::delete('/admin/{id}', [AdminController::class, 'destroy']);
Route::post('/admin/{id}/ubah-password', [AdminController::class, 'ubahPassword']);

// 📌 KARYAWAN CRUD
Route::get('/karyawan', [KaryawanController::class, 'index']);
Route::post('/karyawan', [KaryawanController::class, 'store']);
Route::put('/karyawan/{id}', [KaryawanController::class, 'update']);
Route::delete('/karyawan/{id}', [KaryawanController::class, 'destroy']);
Route::post('/karyawan/{id}/ubah-password', [KaryawanController::class, 'ubahPassword']);
Route::put('/karyawan/{id}/toggle-status', [KaryawanController::class, 'toggleStatus']);

// 📌 LAYANAN CRUD
Route::apiResource('layanan', LayananController::class);

// 📌 PELANGGAN CRUD
Route::apiResource('pelanggan', PelangganController::class);

// 📌 PICKUP SETTINGS ROUTES
Route::prefix('pickup-settings')->name('pickup-settings.')->group(function () {
    
    // 🌍 PUBLIC ROUTES (untuk customer/frontend tanpa auth)
    Route::get('/active', [PickupSettingController::class, 'getActiveSettings'])
        ->name('active');
    
    Route::get('/calculate', [PickupSettingController::class, 'calculateCost'])
        ->name('calculate')
        ->middleware('throttle:60,1');
    
    Route::get('/check', [PickupSettingController::class, 'checkAvailability'])
        ->name('check')
        ->middleware('throttle:60,1');
    
    Route::get('/services', [PickupSettingController::class, 'getAvailableServices'])
        ->name('services')
        ->middleware('throttle:60,1');
    
    Route::get('/stats', [PickupSettingController::class, 'getStats'])
        ->name('stats');
    
    // 🔒 ADMIN ROUTES (tanpa auth dulu untuk testing)
    Route::get('/', [PickupSettingController::class, 'index'])->name('index');
    Route::post('/', [PickupSettingController::class, 'store'])->name('store');
    Route::get('/{id}', [PickupSettingController::class, 'show'])->name('show');
    Route::put('/{id}', [PickupSettingController::class, 'update'])->name('update');
    Route::delete('/{id}', [PickupSettingController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/toggle', [PickupSettingController::class, 'toggleStatus'])->name('toggle');
});

// ✅ ENHANCED TRANSAKSI ROUTES - FIXED ORDER untuk menghindari konflik
Route::prefix('transaksi')->group(function () {
    // ⚠️ IMPORTANT: Routes dengan path spesifik HARUS di atas routes dengan parameter {id}
    
    // Bulk operations (harus di atas {id})
    Route::patch('/bulk/status', [TransaksiController::class, 'bulkUpdateStatus']);
    
    // Reports dan statistik (harus di atas {id})
    Route::get('/reports/daily', [TransaksiController::class, 'laporanHarian']);
    Route::get('/reports/dashboard-stats', [TransaksiController::class, 'dashboardStats']);
    
    // Export (harus di atas {id})
    Route::get('/export/all', [TransaksiController::class, 'exportAllTransactions']);
    
    // Store endpoint (harus di atas {id})
    Route::post('/store', [TransaksiController::class, 'store']); // ✅ Endpoint untuk Pembayaran.vue
    
    // Main CRUD operations
    Route::get('/', [TransaksiController::class, 'index']);
    Route::post('/', [TransaksiController::class, 'store']); // Alternative endpoint
    
    // Routes dengan {id} parameter HARUS DI BAWAH routes spesifik
    Route::get('/{id}', [TransaksiController::class, 'show']);
    Route::patch('/{id}/status', [TransaksiController::class, 'updateStatus']);
    Route::patch('/{id}/payment', [TransaksiController::class, 'updatePayment']); // ✅ TAMBAH INI
    Route::patch('/{id}/pickup-status', [TransaksiController::class, 'updatePickupStatus']); // ✅ TAMBAH INI
    Route::delete('/{id}', [TransaksiController::class, 'destroy']);
    Route::get('/{id}/receipt', [TransaksiController::class, 'getReceipt']);
    Route::get('/{id}/print', [TransaksiController::class, 'printReceipt']);
});

// ✅ NEW: KASIR ROUTES - Khusus untuk DashboardKasir.vue
Route::prefix('kasir')->group(function () {
    Route::get('/orders', [TransaksiController::class, 'orders']);
    Route::get('/payment-updates', [TransaksiController::class, 'getPaymentUpdates']); // ✅ TRACK pembayaran dari kurir
    Route::patch('/orders/{id}/payment', [TransaksiController::class, 'updatePayment']);
    Route::patch('/orders/{id}/status', [TransaksiController::class, 'updateStatus']);
    Route::patch('/orders/{id}/pickup-status', [TransaksiController::class, 'updatePickupStatus']);
});

// 📌 LAPORAN HARIAN (Cash Flow)
Route::get('/laporan-harian', [CashFlowController::class, 'laporanHarian']);

// 📌 CASH FLOW ANALYTICS
Route::prefix('cash-flow')->group(function () {
    Route::get('/summary', [CashFlowController::class, 'getCashFlowSummary']);
    Route::get('/trends', [CashFlowController::class, 'getRevenueTrends']);
    Route::get('/payment-analytics', [CashFlowController::class, 'getPaymentMethodAnalytics']);
});

// 📌 EXPORT LAPORAN
Route::prefix('laporan')->group(function () {
    Route::get('/export/pdf', [CashFlowController::class, 'exportToPDF']);
    Route::get('/export/excel', [CashFlowController::class, 'exportToExcel']);
});

// 📌 ADMIN SPECIFIC ROUTES
Route::prefix('admin')->group(function () {
    Route::get('/laporan', [CashFlowController::class, 'laporanHarian']);
    Route::get('/laporan/export/pdf', [CashFlowController::class, 'exportToPDF']);
    Route::get('/laporan/export/excel', [CashFlowController::class, 'exportToExcel']);
    Route::get('/transaksi/export', [TransaksiController::class, 'exportAllTransactions']);
    Route::get('/transaksi/{id}/print', [TransaksiController::class, 'printReceipt']);
    
    // Pickup settings untuk admin
    Route::get('/pickup-settings', [PickupSettingController::class, 'index']);
    Route::get('/pickup-settings/stats', [PickupSettingController::class, 'getStats']);
});

// ✅ ENHANCED DASHBOARD STATISTICS - Diperbaiki dan dilengkapi
Route::prefix('dashboard')->group(function () {
    Route::get('/today', [TransaksiController::class, 'getTodayStats']); // Perlu ditambah method
    Route::get('/week', [TransaksiController::class, 'getWeekStats']); // Perlu ditambah method
    Route::get('/month', [TransaksiController::class, 'getMonthStats']); // Perlu ditambah method
    Route::get('/recent-transactions', [TransaksiController::class, 'getRecentTransactions']); // Perlu ditambah method
    Route::get('/stats', [TransaksiController::class, 'dashboardStats']); // Link ke method yang sudah ada
    
    // Pickup stats untuk dashboard
    Route::get('/pickup-stats', [PickupSettingController::class, 'getStats']);
});