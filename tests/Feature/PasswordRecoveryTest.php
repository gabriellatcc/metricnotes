<?php

namespace Tests\Feature;

use App\Mail\PasswordRecoveryOtpMail;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_mail_and_returns_generic_success(): void
    {
        Mail::fake();

        $this->seed(AdminUserSeeder::class);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'admin@metricnotes.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(PasswordRecoveryOtpMail::class);
    }

    public function test_forgot_password_unknown_email_still_returns_success_and_sends_no_mail(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertNothingSent();
    }

    public function test_full_recovery_flow_changes_password_and_allows_login(): void
    {
        Mail::fake();

        $this->seed(AdminUserSeeder::class);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'admin@metricnotes.com',
        ]);

        $captured = '';

        Mail::assertSent(PasswordRecoveryOtpMail::class, function (PasswordRecoveryOtpMail $mail) use (&$captured): bool {
            $captured = $mail->code;

            return true;
        });

        $this->assertMatchesRegularExpression('/^\d{4}$/', $captured);

        $verify = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'admin@metricnotes.com',
            'code' => $captured,
        ]);

        $verify->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'reset_session_id',
                    'reset_secret',
                ],
            ]);

        $resetSessionId = $verify->json('data.reset_session_id');
        $secret = $verify->json('data.reset_secret');

        $recover = $this->postJson('/api/auth/recover-password', [
            'reset_session_id' => $resetSessionId,
            'reset_secret' => $secret,
            'password' => 'newPass123',
            'password_confirmation' => 'newPass123',
        ]);

        $recover->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'admin@metricnotes.com',
            'password' => 'admin123456',
        ])->assertStatus(401);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@metricnotes.com',
            'password' => 'newPass123',
        ])->assertOk();
    }

    public function test_recover_rejects_when_new_password_equals_current(): void
    {
        Mail::fake();

        $this->seed(AdminUserSeeder::class);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'admin@metricnotes.com',
        ]);

        $code = '';
        Mail::assertSent(PasswordRecoveryOtpMail::class, function (PasswordRecoveryOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $verify = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'admin@metricnotes.com',
            'code' => $code,
        ]);

        $verify->assertOk();

        $this->postJson('/api/auth/recover-password', [
            'reset_session_id' => $verify->json('data.reset_session_id'),
            'reset_secret' => $verify->json('data.reset_secret'),
            'password' => 'admin123456',
            'password_confirmation' => 'admin123456',
        ])->assertStatus(422);
    }

    public function test_verify_invalid_code_returns_422(): void
    {
        Mail::fake();
        $this->seed(AdminUserSeeder::class);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'admin@metricnotes.com',
        ]);

        $otp = '';

        Mail::assertSent(PasswordRecoveryOtpMail::class, function (PasswordRecoveryOtpMail $mail) use (&$otp): bool {
            $otp = $mail->code;

            return true;
        });

        $wrongCode = sprintf('%04d', ((int) $otp + 37) % 10000);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'admin@metricnotes.com',
            'code' => $wrongCode,
        ]);

        $this->assertNotSame($wrongCode, $otp);
        $response->assertStatus(422);
    }
}
