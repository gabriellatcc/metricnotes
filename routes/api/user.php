<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/', [UserController::class, 'store']);

Route::middleware('jwt')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/{id}/avatar', [UserController::class, 'uploadAvatar']);
    Route::put('/{id}/password', [UserController::class, 'changePassword']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'delete']);
});
