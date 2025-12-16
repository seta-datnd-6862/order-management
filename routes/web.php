<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SummaryController;

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
