<?php

use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReviewController;
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

// Reviews routes
Route::resource('reviews', ReviewController::class)->only(['store', 'show', 'destroy']);
Route::get('rentals/{rental}/review', [ReviewController::class, 'create'])
    ->middleware(['auth'])->name('reviews.create');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
