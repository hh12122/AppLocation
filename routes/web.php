<?php

use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('vehicles.index');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Vehicles routes
Route::resource('vehicles', VehicleController::class);
Route::get('my-vehicles', [VehicleController::class, 'myVehicles'])
    ->middleware(['auth'])->name('vehicles.my');

// Rentals routes
Route::resource('rentals', RentalController::class)->except(['edit', 'update', 'destroy']);
Route::get('rentals/create/{vehicle}', [RentalController::class, 'create'])
    ->middleware(['auth'])->name('rentals.create');
Route::post('rentals/{rental}/confirm', [RentalController::class, 'confirm'])
    ->middleware(['auth'])->name('rentals.confirm');
Route::post('rentals/{rental}/cancel', [RentalController::class, 'cancel'])
    ->middleware(['auth'])->name('rentals.cancel');
Route::post('rentals/{rental}/pickup', [RentalController::class, 'pickup'])
    ->middleware(['auth'])->name('rentals.pickup');
Route::post('rentals/{rental}/return', [RentalController::class, 'return'])
    ->middleware(['auth'])->name('rentals.return');
Route::get('my-rentals', [RentalController::class, 'myRentals'])
    ->middleware(['auth'])->name('rentals.my');
Route::get('my-bookings', [RentalController::class, 'myBookings'])
    ->middleware(['auth'])->name('rentals.bookings');
Route::get('rentals/{rental}/contract', [RentalController::class, 'exportContract'])
    ->middleware(['auth'])->name('rentals.contract');

// Reviews routes
Route::resource('reviews', ReviewController::class);
Route::get('rentals/{rental}/review', [ReviewController::class, 'create'])
    ->middleware(['auth'])->name('reviews.create');
Route::post('rentals/{rental}/review', [ReviewController::class, 'store'])
    ->middleware(['auth'])->name('reviews.store');
Route::get('vehicles/{vehicle}/reviews', [ReviewController::class, 'vehicleReviews'])
    ->name('reviews.vehicle');
Route::get('users/{user}/reviews', [ReviewController::class, 'userReviews'])
    ->name('reviews.user');

// Favorites routes
Route::middleware('auth')->group(function () {
    Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::put('favorites/{favorite}', [FavoriteController::class, 'update'])->name('favorites.update');
    Route::delete('favorites/{vehicleId}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::post('favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::get('favorites/check/{vehicleId}', [FavoriteController::class, 'check'])->name('favorites.check');
});

// License verification routes
Route::middleware(['auth'])->group(function () {
    Route::get('license-verification', [\App\Http\Controllers\LicenseVerificationController::class, 'show'])
        ->name('license.verification');
    Route::post('license-verification', [\App\Http\Controllers\LicenseVerificationController::class, 'upload'])
        ->name('license.upload');
});

// Admin routes for license verification
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('license-verifications', [\App\Http\Controllers\LicenseVerificationController::class, 'index'])
        ->name('admin.license-verifications');
    Route::post('users/{user}/verify-license', [\App\Http\Controllers\LicenseVerificationController::class, 'verify'])
        ->name('admin.license-verifications.verify');
});

// Payment routes
Route::middleware(['auth'])->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{rental}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('payments/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    
    // API routes for payment processing
    Route::post('api/payments/stripe/create-intent', [PaymentController::class, 'createStripeIntent'])
        ->name('payments.stripe.create-intent');
    Route::post('api/payments/paypal/create-order', [PaymentController::class, 'createPayPalOrder'])
        ->name('payments.paypal.create-order');
    
    // PayPal return URLs
    Route::get('payments/paypal/success', [PaymentController::class, 'success'])->name('payments.paypal.success');
    Route::get('payments/paypal/cancel', [PaymentController::class, 'cancel'])->name('payments.paypal.cancel');
    
    // Refund endpoint for owners and admins
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
});

// Webhook routes (no auth middleware)
Route::post('webhooks/stripe', [PaymentController::class, 'stripeWebhook'])->name('webhooks.stripe');

// Chat routes
Route::middleware(['auth'])->group(function () {
    Route::get('chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{conversation}', [\App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
    Route::post('chat/rental/{rental}', [\App\Http\Controllers\ChatController::class, 'createForRental'])->name('chat.create-rental');
    Route::post('chat/{conversation}/archive', [\App\Http\Controllers\ChatController::class, 'archive'])->name('chat.archive');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
