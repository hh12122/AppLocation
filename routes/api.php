<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\GeoNotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Geo-notification API routes
Route::middleware(['auth'])->group(function () {
    // Geo-notification API routes
    Route::prefix('geo-notifications')->group(function () {
        Route::post('/location', [GeoNotificationController::class, 'updateLocation'])
            ->name('api.geo-notifications.update-location');
        Route::get('/location-history', [GeoNotificationController::class, 'getLocationHistory'])
            ->name('api.geo-notifications.location-history');
        Route::get('/nearby', [GeoNotificationController::class, 'getNearbyNotifications'])
            ->name('api.geo-notifications.nearby');
        Route::post('/{notification}/read', [GeoNotificationController::class, 'markAsRead'])
            ->name('api.geo-notifications.mark-read');
        Route::post('/{notification}/clicked', [GeoNotificationController::class, 'markAsClicked'])
            ->name('api.geo-notifications.mark-clicked');
        
        // Testing endpoint (only in local environment)
        Route::post('/test', [GeoNotificationController::class, 'testNotification'])
            ->name('api.geo-notifications.test');
        
        // Statistics (admin only)
        Route::get('/statistics', [GeoNotificationController::class, 'getStatistics'])
            ->name('api.geo-notifications.statistics');
    });
});