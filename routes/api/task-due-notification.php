<?php

use App\Http\Controllers\TaskDueNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function () {
    Route::get('/', [TaskDueNotificationController::class, 'index']);
    Route::patch('/read-all', [TaskDueNotificationController::class, 'markAllRead']);
    Route::patch('/clear', [TaskDueNotificationController::class, 'clearAll']);
    Route::patch('/{task}/read', [TaskDueNotificationController::class, 'markRead']);
});
