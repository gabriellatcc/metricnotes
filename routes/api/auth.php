<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function (): void {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::middleware('throttle:20,1')->group(function (): void {
    Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
    Route::post('/recover-password', [AuthController::class, 'recoverPassword']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
Route::middleware('jwt')->get('/me', [AuthController::class, 'me']);
