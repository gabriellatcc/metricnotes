<?php

use App\Http\Controllers\NoteTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function () {
    Route::get('/', [NoteTypeController::class, 'index']);
    Route::get('/{id}', [NoteTypeController::class, 'show']);
    Route::post('/', [NoteTypeController::class, 'store']);
    Route::put('/{id}', [NoteTypeController::class, 'update']);
    Route::delete('/{id}', [NoteTypeController::class, 'delete']);
});
