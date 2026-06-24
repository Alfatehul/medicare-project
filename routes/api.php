<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\NutritionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded within the "api" middleware group and are prefixed
| with /api. Routes under /v1 use versioned controllers.
|
*/

// Authenticated user profile
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'data'   => $request->user(),
    ]);
});

// ─── API v1 ─────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Authentication (public)
    Route::prefix('auth')->group(function () {
        Route::post('/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    // Doctors (public)
    Route::get('/doctors', [DoctorController::class, 'index']);

    // Currency conversion (public)
    Route::get('/currency/convert', [CurrencyController::class, 'convert']);

    // Payments webhook (public — no auth, receives Midtrans notifications)
    Route::post('/payments/webhook', [AppointmentController::class, 'webhook']);

    // Authenticated API endpoints
    Route::middleware('auth:sanctum')->group(function () {
        // Appointments booking
        Route::post('/appointments', [AppointmentController::class, 'store']);

        // Nutrition Facts Search
        Route::get('/nutrition/search', [NutritionController::class, 'search']);
    });
});
