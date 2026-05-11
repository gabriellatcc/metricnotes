<?php

namespace App\Services;

use App\Mail\PasswordRecoveryOtpMail;
use App\Models\PasswordRecoverySession;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    public const FORGOT_SUCCESS_MESSAGE = 'Se existir uma conta para este e-mail, enviamos um código para redefinição da senha.';

    private const OTP_TTL_MINUTES = 15;

    private const RECOVER_TTL_MINUTES = 15;

    private const MAX_OTP_ATTEMPTS = 5;

    public function requestForgotPassword(string $email): void
    {
        $user = $this->findUserByEmail($email);
        if (! $user) {
            return;
        }

        PasswordRecoverySession::query()->where('user_id', $user->id)->delete();

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        PasswordRecoverySession::query()->create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'otp_attempts' => 0,
        ]);

        Mail::to($user->email)->send(new PasswordRecoveryOtpMail($code));
    }

    /**
     * @return array{reset_session_id: string, reset_secret: string}
     */
    public function verifyOtp(string $email, string $code): array
    {
        $user = $this->findUserByEmail($email);
        if (! $user) {
            throw new Exception('Código inválido ou expirado.', 422);
        }

        /** @var PasswordRecoverySession|null $session */
        $session = PasswordRecoverySession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('otp_hash')
            ->orderByDesc('created_at')
            ->first();

        if (! $session || $session->otp_expires_at->isPast()) {
            throw new Exception('Código inválido ou expirado.', 422);
        }

        if ($session->otp_attempts >= self::MAX_OTP_ATTEMPTS) {
            throw new Exception('Código bloqueado após várias tentativas. Solicite um novo código.', 422);
        }

        if (! Hash::check($code, $session->otp_hash ?? '')) {
            $session->increment('otp_attempts');

            throw new Exception('Código inválido ou expirado.', 422);
        }

        return DB::transaction(function () use ($session) {
            $plainSecret = Str::random(64);

            $session->otp_hash = null;
            $session->otp_attempts = 0;
            $session->recover_secret_hash = Hash::make($plainSecret);
            $session->recover_expires_at = now()->addMinutes(self::RECOVER_TTL_MINUTES);
            $session->save();

            return [
                'reset_session_id' => $session->id,
                'reset_secret' => $plainSecret,
            ];
        });
    }

    /** @param  array{reset_session_id: string, reset_secret: string, password: string, password_confirmation: string}  $payload */
    public function resetPassword(array $payload): void
    {
        $session = PasswordRecoverySession::query()
            ->whereKey($payload['reset_session_id'])
            ->first();

        if (
            ! $session
            || empty($session->recover_secret_hash)
            || empty($session->recover_expires_at)
            || $session->recover_expires_at->isPast()
        ) {
            throw new Exception('Sessão de recuperação inválida ou expirada.', 422);
        }

        if (! Hash::check($payload['reset_secret'], $session->recover_secret_hash)) {
            throw new Exception('Sessão de recuperação inválida ou expirada.', 422);
        }

        $user = User::query()->find($session->user_id);
        if (! $user) {
            throw new Exception('Sessão de recuperação inválida ou expirada.', 422);
        }

        if (Hash::check($payload['password'], $user->password)) {
            throw new Exception('A nova senha deve ser diferente da senha atual.', 422);
        }

        DB::transaction(function () use ($user, $payload) {
            $user->password = $payload['password'];
            $user->save();

            PasswordRecoverySession::query()->where('user_id', $user->id)->delete();
        });
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])
            ->first();
    }
}
