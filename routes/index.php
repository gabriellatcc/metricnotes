<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/auth.php'));
Route::prefix('user')->group(base_path('routes/user.php'));
