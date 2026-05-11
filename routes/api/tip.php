<?php

use App\Http\Controllers\TipController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function () {
    Route::get('/', [TipController::class, 'index']);
    Route::get('/{id}', [TipController::class, 'show']);
    Route::post('/', [TipController::class, 'store']);
    Route::put('/{id}', [TipController::class, 'update']);
    Route::delete('/{id}', [TipController::class, 'delete']);
});
