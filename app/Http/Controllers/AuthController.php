<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login(
            $request->string('email')->toString(),
            $request->string('password')->toString()
        );

        if (! $result) {
            return $this->respondError('Invalid credentials.', null, 401);
        }

        return $this->respondSuccess($result, 'Login successful.');
    }

    public function me()
    {
        $user = $this->authService->me();

        if (! $user) {
            return $this->respondError('Invalid or expired access token.', null, 401);
        }

        return $this->respondSuccess($user, 'Authenticated user fetched successfully.');
    }

    public function refreshToken(RefreshTokenRequest $request)
    {
        $result = $this->authService->refreshToken(
            $request->string('refresh_token')->toString()
        );

        if (! $result) {
            return $this->respondError('Invalid or expired refresh token.', null, 401);
        }

        return $this->respondSuccess($result, 'Token refreshed successfully.');
    }
}
