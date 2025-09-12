<?php

use App\Http\Controllers\ChatController;
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

// Broadcasting authentication routes
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Chat API routes
Route::middleware(['auth'])->group(function () {
    Route::prefix('chat')->group(function () {
        Route::post('/send', [ChatController::class, 'store'])->name('api.chat.send');
        Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages'])
            ->name('api.chat.messages');
        Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])
            ->name('api.chat.send-message');
        Route::post('/conversations/{conversation}/mark-read', [ChatController::class, 'markAsRead'])
            ->name('api.chat.mark-read');
        Route::get('/unread-count', [ChatController::class, 'getUnreadCount'])
            ->name('api.chat.unread-count');
    });
});