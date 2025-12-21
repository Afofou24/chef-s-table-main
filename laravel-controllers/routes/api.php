<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\RestaurantTableController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StockItemController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ActivityLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public authentication routes
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Authentication routes (protected)
    Route::get('auth/user', [AuthController::class, 'user']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    // Roles
    Route::apiResource('roles', RoleController::class);

    // Users
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::apiResource('users', UserController::class);

    // Categories
    Route::post('categories/reorder', [CategoryController::class, 'reorder']);
    Route::apiResource('categories', CategoryController::class);

    // Menu Items
    Route::post('menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
    Route::post('menu-items/{menuItem}/toggle-featured', [MenuItemController::class, 'toggleFeatured']);
    Route::apiResource('menu-items', MenuItemController::class);

    // Restaurant Tables
    Route::get('tables/available', [RestaurantTableController::class, 'available']);
    Route::patch('tables/{restaurantTable}/status', [RestaurantTableController::class, 'updateStatus']);
    Route::apiResource('tables', RestaurantTableController::class);

    // Orders
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('orders/{order}/items', [OrderController::class, 'addItems']);
    Route::apiResource('orders', OrderController::class);

    // Order Items (Kitchen)
    Route::get('kitchen/items', [OrderItemController::class, 'kitchen']);
    Route::patch('order-items/{orderItem}/status', [OrderItemController::class, 'updateStatus']);
    Route::apiResource('order-items', OrderItemController::class)->except(['store']);

    // Payments
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
    Route::get('payments/daily-summary', [PaymentController::class, 'dailySummary']);
    Route::apiResource('payments', PaymentController::class);

    // Stock Items
    Route::get('stock/low', [StockItemController::class, 'lowStock']);
    Route::get('stock/expiring', [StockItemController::class, 'expiringSoon']);
    Route::post('stock/{stockItem}/adjust', [StockItemController::class, 'adjustQuantity']);
    Route::apiResource('stock', StockItemController::class);

    // Reservations
    Route::get('reservations/today', [ReservationController::class, 'today']);
    Route::patch('reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
    Route::apiResource('reservations', ReservationController::class);

    // Settings
    Route::get('settings/grouped', [SettingController::class, 'grouped']);
    Route::put('settings/bulk', [SettingController::class, 'bulkUpdate']);
    Route::apiResource('settings', SettingController::class);

    // Backups
    Route::get('backups/{backup}/download', [BackupController::class, 'download']);
    Route::post('backups/{backup}/restore', [BackupController::class, 'restore']);
    Route::post('backups/clean-old', [BackupController::class, 'cleanOld']);
    Route::apiResource('backups', BackupController::class);

    // Activity Logs
    Route::get('activity-logs/summary', [ActivityLogController::class, 'summary']);
    Route::get('activity-logs/for-model', [ActivityLogController::class, 'forModel']);
    Route::post('activity-logs/clean-old', [ActivityLogController::class, 'cleanOld']);
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
});
