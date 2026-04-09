<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@metricnotes.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123456'),
                'is_admin' => true,
            ]
        );
    }
}
