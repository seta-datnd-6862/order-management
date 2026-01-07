<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryImportController;
use App\Http\Controllers\InventoryExportController;
use App\Http\Controllers\ViettelPostController;


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

    // Đơn xuất kho
    Route::resource('exports', InventoryExportController::class);
});
    
// Viettel Post routes
Route::prefix('viettel-posts')->name('viettel-posts.')->group(function () {
    // Danh sách
    Route::get('/', [ViettelPostController::class, 'index'])->name('index');
    
    // Import từ mã vận chuyển có sẵn
    Route::get('/import', [ViettelPostController::class, 'importForm'])->name('import-form');
    Route::post('/import', [ViettelPostController::class, 'import'])->name('import');
    
    // Tạo từ Order
    Route::get('/create-from-order/{order}', [ViettelPostController::class, 'createFromOrder'])->name('create-from-order');
    Route::post('/store-from-order/{order}', [ViettelPostController::class, 'storeFromOrder'])->name('store-from-order');

    // NEW: Calculate shipping fee (AJAX)
    Route::post('/calculate-shipping', [ViettelPostController::class, 'calculateShipping'])->name('calculate-shipping');
    // NEW: Get all services with prices
    Route::post('/get-services', [ViettelPostController::class, 'getServicesWithPrices'])
        ->name('get-services');
    
    // Chi tiết & actions
    Route::get('/{viettelOrder}', [ViettelPostController::class, 'show'])->name('show');
    Route::patch('/{viettelOrder}/status', [ViettelPostController::class, 'updateStatus'])->name('update-status');
    Route::delete('/{viettelOrder}', [ViettelPostController::class, 'destroy'])->name('destroy');
});
