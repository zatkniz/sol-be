<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\PublicBookingController;

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

// Public booking routes (no authentication required)
Route::prefix('public')->group(function () {
    Route::get('/services', [PublicBookingController::class, 'getServices']);
    Route::get('/employers', [PublicBookingController::class, 'getEmployers']);
    Route::get('/available-slots', [PublicBookingController::class, 'getAvailableSlots']);
    Route::post('/bookings', [PublicBookingController::class, 'createBooking']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/client-stats/{client}', [ClientController::class, 'getStats']);
    Route::get('/all-clients', [ClientController::class, 'getAllClients']);
    Route::post('/clients', [ClientController::class, 'save']);
    Route::delete('/clients/{client}', [ClientController::class, 'delete']);

    Route::get('/employers', [EmployerController::class, 'index']);
    Route::get('/all-employers', [EmployerController::class, 'getAllEmployers']);
    Route::post('/employers', [EmployerController::class, 'save']);
    Route::delete('/employers/{employer}', [EmployerController::class, 'delete']);
    Route::post('/employers-order', [EmployerController::class, 'reorder']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/all-services', [ServiceController::class, 'getAllServices']);
    Route::post('/services', [ServiceController::class, 'save']);
    Route::delete('/services/{service}', [ServiceController::class, 'delete']);
    Route::post('/services-order', [ServiceController::class, 'reorder']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/all-users', [UserController::class, 'getAllUsers']);
    Route::post('/users', [UserController::class, 'save']);
    Route::delete('/users/{service}', [UserController::class, 'delete']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/monthly-bookings', [BookingController::class, 'monthlyBookings']);
    Route::post('/bookings', [BookingController::class, 'save']);
    Route::post('/get-available-employers', [BookingController::class, 'getAvailableEmployers']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'delete']);

    Route::post('/schedule', [ScheduleController::class, 'save']);
    Route::get('/schedule/{employer}', [ScheduleController::class, 'getByEmployer']);
    Route::get('/schedule', [ScheduleController::class, 'getAll']);

    Route::get('/statistics', [StatsController::class, 'index']);
    Route::get('/statistics/charts', [StatsController::class, 'chartData']);
    Route::get('/statistics/tables', [StatsController::class, 'tableData']);
});

// Test endpoint (remove in production)
Route::get('/statistics-test', [StatsController::class, 'index']);
