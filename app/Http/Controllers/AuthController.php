<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\ResetPasswordRecoveryRequest;
use App\Http\Requests\Auth\VerifyRecoveryCodeRequest;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function login(LoginRequest $request)
    {
        try {
            $data = $this->authService->login($request->validated());

            return $this->respondSuccess($data, 'Bem vindo!');
        } catch (Exception $e) {
            $code = ($e->getCode() >= 100 && $e->getCode() <= 599)
                ? (int) $e->getCode()
                : 500;

            return $this->respondError(''.$e->getMessage(), null, $code);
        }
    }

    public function me()
    {
        try {
            $data = $this->authService->me();

            return $this->respondSuccess($data, 'Autenticação feita com sucesso.');
        } catch (Exception $e) {
            $code = $e->getCode() ?: 401;

            return $this->respondError('Token inválido ou expirado.', null, $code);
        }
    }

    public function refreshToken(RefreshTokenRequest $request)
    {
        try {
            $data = $this->authService->refreshToken($request->validated());

            return $this->respondSuccess($data, 'Token recarregado com sucesso.');
        } catch (Exception $e) {
            $code = $e->getCode() ?: 401;

            return $this->respondError('Não foi possível atualizar o token.', null, $code);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $this->passwordResetService->requestForgotPassword($request->validated()['email']);

            return $this->respondSuccess(null, PasswordResetService::FORGOT_SUCCESS_MESSAGE);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 100 && $e->getCode() <= 599)
                ? (int) $e->getCode()
                : 500;

            return $this->respondError('Erro ao solicitar recuperação: '.$e->getMessage(), null, $code);
        }
    }

    public function verifyResetCode(VerifyRecoveryCodeRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = $this->passwordResetService->verifyOtp($validated['email'], $validated['code']);

            return $this->respondSuccess($data, 'Código confirmado.');
        } catch (Exception $e) {
            $code = ($e->getCode() >= 100 && $e->getCode() <= 599)
                ? (int) $e->getCode()
                : 500;

            return $this->respondError($e->getMessage(), null, $code);
        }
    }

    public function recoverPassword(ResetPasswordRecoveryRequest $request)
    {
        try {
            $this->passwordResetService->resetPassword($request->validated());

            return $this->respondSuccess(null, 'Senha redefinida com sucesso! Faça login com a nova senha.');
        } catch (Exception $e) {
            $code = ($e->getCode() >= 100 && $e->getCode() <= 599)
                ? (int) $e->getCode()
                : 500;

            return $this->respondError('Erro ao redefinir senha: '.$e->getMessage(), null, $code);
        }
    }
}
