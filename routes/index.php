<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('user')->group(base_path('routes/api/user.php'));