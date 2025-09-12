<?php

use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Vehicles routes
Route::resource('vehicles', VehicleController::class);
Route::get('my-vehicles', [VehicleController::class, 'myVehicles'])
    ->middleware(['auth'])->name('vehicles.my');

// Properties routes
Route::resource('properties', App\Http\Controllers\PropertyController::class);
Route::get('my-properties', [App\Http\Controllers\PropertyController::class, 'myProperties'])
    ->middleware(['auth'])->name('properties.my');

// Property bookings routes
Route::middleware(['auth'])->group(function () {
    Route::get('properties/{property}/book', [App\Http\Controllers\PropertyBookingController::class, 'create'])
        ->name('property-bookings.create');
    Route::post('properties/{property}/book', [App\Http\Controllers\PropertyBookingController::class, 'store'])
        ->name('property-bookings.store');
    Route::get('property-bookings/{booking}', [App\Http\Controllers\PropertyBookingController::class, 'show'])
        ->name('property-bookings.show');
    Route::post('property-bookings/{booking}/confirm', [App\Http\Controllers\PropertyBookingController::class, 'confirm'])
        ->name('property-bookings.confirm');
    Route::post('property-bookings/{booking}/cancel', [App\Http\Controllers\PropertyBookingController::class, 'cancel'])
        ->name('property-bookings.cancel');
    Route::post('property-bookings/{booking}/checkin', [App\Http\Controllers\PropertyBookingController::class, 'checkIn'])
        ->name('property-bookings.checkin');
    Route::post('property-bookings/{booking}/checkout', [App\Http\Controllers\PropertyBookingController::class, 'checkOut'])
        ->name('property-bookings.checkout');
    Route::get('my-property-bookings', [App\Http\Controllers\PropertyBookingController::class, 'myBookings'])
        ->name('property-bookings.my');
    Route::get('property-bookings-management', [App\Http\Controllers\PropertyBookingController::class, 'propertyBookings'])
        ->name('property-bookings.management');
});

// Equipment routes
Route::resource('equipment', App\Http\Controllers\EquipmentController::class);
Route::get('equipment/category/{category}', [App\Http\Controllers\EquipmentController::class, 'category'])
    ->name('equipment.category');
Route::get('my-equipment', [App\Http\Controllers\EquipmentController::class, 'myEquipment'])
    ->middleware(['auth'])->name('equipment.my');

// Equipment bookings routes
Route::middleware(['auth'])->group(function () {
    Route::get('equipment/{equipment}/book', [App\Http\Controllers\EquipmentBookingController::class, 'create'])
        ->name('equipment-bookings.create');
    Route::post('equipment/{equipment}/book', [App\Http\Controllers\EquipmentBookingController::class, 'store'])
        ->name('equipment-bookings.store');
    Route::get('equipment-bookings/{booking}', [App\Http\Controllers\EquipmentBookingController::class, 'show'])
        ->name('equipment-bookings.show');
    Route::post('equipment-bookings/{booking}/confirm', [App\Http\Controllers\EquipmentBookingController::class, 'confirm'])
        ->name('equipment-bookings.confirm');
    Route::post('equipment-bookings/{booking}/cancel', [App\Http\Controllers\EquipmentBookingController::class, 'cancel'])
        ->name('equipment-bookings.cancel');
    Route::post('equipment-bookings/{booking}/ready', [App\Http\Controllers\EquipmentBookingController::class, 'markAsReady'])
        ->name('equipment-bookings.ready');
    Route::post('equipment-bookings/{booking}/delivered', [App\Http\Controllers\EquipmentBookingController::class, 'markAsDelivered'])
        ->name('equipment-bookings.delivered');
    Route::post('equipment-bookings/{booking}/returned', [App\Http\Controllers\EquipmentBookingController::class, 'markAsReturned'])
        ->name('equipment-bookings.returned');
    Route::post('equipment-bookings/{booking}/extension', [App\Http\Controllers\EquipmentBookingController::class, 'requestExtension'])
        ->name('equipment-bookings.extension');
    Route::get('my-equipment-bookings', [App\Http\Controllers\EquipmentBookingController::class, 'myBookings'])
        ->name('equipment-bookings.my');
    Route::get('equipment-bookings-management', [App\Http\Controllers\EquipmentBookingController::class, 'equipmentBookings'])
        ->name('equipment-bookings.management');
});

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
    
    // Item-agnostic check route
    Route::get('favorites/check/{itemId}', [FavoriteController::class, 'check'])->name('favorites.check');
    
    // Legacy vehicle-specific route for backwards compatibility
    Route::get('favorites/check-vehicle/{vehicleId}', [FavoriteController::class, 'checkVehicle'])->name('favorites.check.vehicle');
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

// Referral routes
Route::middleware(['auth'])->group(function () {
    Route::get('referrals', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('referrals/rewards', [\App\Http\Controllers\ReferralController::class, 'rewards'])->name('referrals.rewards');
    Route::get('referrals/leaderboard', [\App\Http\Controllers\ReferralController::class, 'leaderboard'])->name('referrals.leaderboard');
    Route::post('referrals/generate-code', [\App\Http\Controllers\ReferralController::class, 'generateCode'])->name('referrals.generate-code');
    Route::get('referrals/stats', [\App\Http\Controllers\ReferralController::class, 'getStats'])->name('referrals.stats');
    Route::post('referrals/share', [\App\Http\Controllers\ReferralController::class, 'share'])->name('referrals.share');
});

// Public referral routes
Route::post('referrals/validate-code', [\App\Http\Controllers\ReferralController::class, 'validateCode'])->name('referrals.validate-code');
Route::post('referrals/process', [\App\Http\Controllers\ReferralController::class, 'processReferral'])->name('referrals.process');
Route::post('referrals/mark-conversion', [\App\Http\Controllers\ReferralController::class, 'markConversion'])->name('referrals.mark-conversion');

// Localization routes
Route::post('localization/change-locale', [\App\Http\Controllers\LocalizationController::class, 'changeLocale'])
    ->name('localization.change-locale');
Route::get('api/localization/languages', [\App\Http\Controllers\LocalizationController::class, 'getLanguages'])
    ->name('localization.languages');
Route::get('api/localization/translations', [\App\Http\Controllers\LocalizationController::class, 'getTranslations'])
    ->name('localization.translations');

Route::middleware(['auth'])->group(function () {
    Route::post('localization/preferences', [\App\Http\Controllers\LocalizationController::class, 'updatePreferences'])
        ->name('localization.preferences');
});

// Admin localization routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('translations', [\App\Http\Controllers\LocalizationController::class, 'manageTranslations'])
        ->name('admin.translations');
    Route::post('translations/update', [\App\Http\Controllers\LocalizationController::class, 'updateTranslation'])
        ->name('admin.translations.update');
    Route::post('translations/import', [\App\Http\Controllers\LocalizationController::class, 'importTranslations'])
        ->name('admin.translations.import');
    Route::get('translations/export', [\App\Http\Controllers\LocalizationController::class, 'exportTranslations'])
        ->name('admin.translations.export');
    Route::post('languages/add', [\App\Http\Controllers\LocalizationController::class, 'addLanguage'])
        ->name('admin.languages.add');
    Route::post('languages/{language}/toggle', [\App\Http\Controllers\LocalizationController::class, 'toggleLanguage'])
        ->name('admin.languages.toggle');
    Route::post('languages/{language}/default', [\App\Http\Controllers\LocalizationController::class, 'setDefaultLanguage'])
        ->name('admin.languages.default');
});

// AI Recommendations routes
Route::prefix('api/ai')->middleware(['auth'])->group(function () {
    Route::get('recommendations', [\App\Http\Controllers\AIRecommendationController::class, 'getPersonalizedRecommendations']);
    Route::get('trending', [\App\Http\Controllers\AIRecommendationController::class, 'getTrendingItems']);
    Route::get('search-suggestions', [\App\Http\Controllers\AIRecommendationController::class, 'getSearchSuggestions']);
    Route::post('track-activity', [\App\Http\Controllers\AIRecommendationController::class, 'trackActivity']);
    Route::post('track-search', [\App\Http\Controllers\AIRecommendationController::class, 'trackSearch']);
    Route::post('search/{searchId}/success', [\App\Http\Controllers\AIRecommendationController::class, 'markSearchSuccess']);
    Route::post('recommendations/{id}/viewed', [\App\Http\Controllers\AIRecommendationController::class, 'markRecommendationViewed']);
    Route::post('recommendations/{id}/clicked', [\App\Http\Controllers\AIRecommendationController::class, 'markRecommendationClicked']);
    Route::post('recommendations/{id}/converted', [\App\Http\Controllers\AIRecommendationController::class, 'markRecommendationConverted']);
    Route::post('recommendations/{id}/feedback', [\App\Http\Controllers\AIRecommendationController::class, 'provideFeedback']);
});

Route::get('ai/dashboard', [\App\Http\Controllers\AIRecommendationController::class, 'dashboard'])
    ->middleware(['auth'])->name('ai.dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
