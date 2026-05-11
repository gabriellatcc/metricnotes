<?php

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_with_seeded_admin(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@metricnotes.com',
            'password' => 'admin123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'authorization' => ['access_token', 'token_type', 'expires_in'],
                ],
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@metricnotes.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
