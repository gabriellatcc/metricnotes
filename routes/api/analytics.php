<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function () {
    Route::get('/weekly', [AnalyticsController::class, 'weekly']);
});
