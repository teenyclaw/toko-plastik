<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\SupplierController;
use App\Http\Controllers\Catalog\UnitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\ReceiptController;
use App\Http\Controllers\Pos\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/up', HealthController::class)->name('health');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:owner,kasir')->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/products', [PosController::class, 'products'])->name('products');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/receipt/{sale}', [ReceiptController::class, 'show'])->name('receipt');
    });

    Route::middleware('role:owner,gudang')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('units', UnitController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('suppliers', SupplierController::class)->except(['show']);

        Route::get('/purchases/products', [PurchaseController::class, 'products'])->name('purchases.products');
        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::post('/stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/collect', [FinanceController::class, 'collect'])->name('finance.collect');
        Route::resource('expenses', ExpenseController::class)->except(['show']);

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
