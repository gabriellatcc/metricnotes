<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('user')->group(base_path('routes/api/user.php'));
Route::prefix('task')->group(base_path('routes/api/task.php'));
//Route::prefix('task-type')->group(base_path('routes/api/task-type.php'));
