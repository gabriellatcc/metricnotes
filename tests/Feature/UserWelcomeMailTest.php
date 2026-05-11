<?php

namespace Tests\Feature;

use App\Mail\WelcomeUserMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserWelcomeMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_user_sends_welcome_mail(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/user', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(WelcomeUserMail::class, function (WelcomeUserMail $mail): bool {
            return $mail->user->email === 'novo@example.com'
                && $mail->user->name === 'Novo Usuário';
        });
    }
}
