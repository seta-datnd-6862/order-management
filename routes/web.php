<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryImportController;

// Trang chủ - redirect đến orders
Route::get('/', function () {
    return redirect()->route('orders.index');
});

// Khách hàng
Route::resource('customers', CustomerController::class)->except(['show']);

// Sản phẩm
Route::resource('products', ProductController::class)->except(['show']);

// Đơn hàng
Route::resource('orders', OrderController::class);
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
Route::post('orders/bulk-status', [OrderController::class, 'bulkUpdateStatus'])->name('orders.bulkUpdateStatus');

// Tổng hợp
Route::get('summary', [SummaryController::class, 'index'])->name('summary.index');
Route::post('summary/move-to-next-status', [SummaryController::class, 'moveToNextStatus'])->name('summary.moveToNextStatus');

// Kho hàng
Route::prefix('inventory')->name('inventory.')->group(function () {
    // Thống kê kho
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/detail/{product}', [InventoryController::class, 'detail'])->name('detail');
    
    // Đơn nhập kho
    Route::resource('imports', InventoryImportController::class);
});
