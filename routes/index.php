<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('user')->group(base_path('routes/api/user.php'));
Route::prefix('task')->group(base_path('routes/api/task.php'));
Route::prefix('tip')->group(base_path('routes/api/tip.php'));
Route::prefix('note')->group(base_path('routes/api/note.php'));
Route::prefix('analytics')->group(base_path('routes/api/analytics.php'));
Route::prefix('notification/task-due')->group(base_path('routes/api/task-due-notification.php'));
