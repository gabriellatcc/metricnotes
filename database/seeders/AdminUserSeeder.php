<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@metricnotes.com'],
            [
                'name' => 'Admin',
                'password' => 'admin123456',
                'is_admin' => true,
            ]
        );

        if ($this->command !== null) {
            $this->command->info('Usuário admin garantido: admin@metricnotes.com (senha: admin123456).');
        }
    }
}
