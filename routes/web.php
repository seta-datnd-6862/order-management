<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryImportController;
use App\Http\Controllers\InventoryExportController;
use App\Http\Controllers\ViettelPostController;
use App\Http\Controllers\ChatbotController;

// Guest routes (chỉ truy cập khi chưa đăng nhập)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout (cần đăng nhập)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Tất cả routes bảo vệ bởi auth
Route::middleware('auth')->group(function () {
    // Trang chủ - redirect đến orders
    Route::get('/', function () {
        return redirect()->route('orders.index');
    });

    // Khách hàng
    Route::resource('customers', CustomerController::class)->except(['show']);

    // Sản phẩm
    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('products/import', [ProductController::class, 'importView'])->name('products.import');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import.store');
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
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/detail/{product}', [InventoryController::class, 'detail'])->name('detail');
        Route::resource('imports', InventoryImportController::class);
        Route::resource('exports', InventoryExportController::class);
    });

    // Chatbot routes
    Route::prefix('chatbot')->name('chatbot.')->group(function () {
        Route::get('/search-customers', [ChatbotController::class, 'searchCustomers'])->name('search-customers');
        Route::get('/search-products', [ChatbotController::class, 'searchProducts'])->name('search-products');
        Route::post('/create-customer', [ChatbotController::class, 'createCustomer'])->name('create-customer');
        Route::post('/create-product', [ChatbotController::class, 'createProduct'])->name('create-product');
        Route::post('/create-order', [ChatbotController::class, 'createOrder'])->name('create-order');
    });

    // Viettel Post routes
    Route::prefix('viettel-posts')->name('viettel-posts.')->group(function () {
        Route::get('/', [ViettelPostController::class, 'index'])->name('index');
        Route::get('/import', [ViettelPostController::class, 'importForm'])->name('import-form');
        Route::post('/import', [ViettelPostController::class, 'import'])->name('import');
        Route::get('/create-from-order/{order}', [ViettelPostController::class, 'createFromOrder'])->name('create-from-order');
        Route::post('/store-from-order/{order}', [ViettelPostController::class, 'storeFromOrder'])->name('store-from-order');
        Route::post('/calculate-shipping', [ViettelPostController::class, 'calculateShipping'])->name('calculate-shipping');
        Route::post('/get-services', [ViettelPostController::class, 'getServicesWithPrices'])->name('get-services');
        Route::get('/{viettelOrder}', [ViettelPostController::class, 'show'])->name('show');
        Route::patch('/{viettelOrder}/status', [ViettelPostController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{viettelOrder}', [ViettelPostController::class, 'destroy'])->name('destroy');
    });
});
