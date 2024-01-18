<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookingController;

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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/client-stats/{client}', [ClientController::class, 'getStats']);
    Route::get('/all-clients', [ClientController::class, 'getAllClients']);
    Route::post('/clients', [ClientController::class, 'save']);
    Route::delete('/clients/{client}', [ClientController::class, 'delete']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/all-services', [ServiceController::class, 'getAllServices']);
    Route::post('/services', [ServiceController::class, 'save']);
    Route::delete('/services/{service}', [ServiceController::class, 'delete']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/all-users', [UserController::class, 'getAllUsers']);
    Route::post('/users', [UserController::class, 'save']);
    Route::delete('/users/{service}', [UserController::class, 'delete']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/monthly-bookings', [BookingController::class, 'monthlyBookings']);
    Route::post('/bookings', [BookingController::class, 'save']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'delete']);
});
