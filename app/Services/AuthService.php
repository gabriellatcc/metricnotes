<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(string $email, string $password): ?array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $this->tokenPayload($user);
    }

    public function me(): ?User
    {
        return JWTAuth::parseToken()->authenticate();
    }

    public function refreshToken(string $refreshToken): ?array
    {
        $newAccessToken = JWTAuth::setToken($refreshToken)->refresh();
        $user = JWTAuth::setToken($newAccessToken)->toUser();

        if (! $user) {
            return null;
        }

        return [
            'user' => $user,
            'access_token' => $newAccessToken,
            'refresh_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    private function tokenPayload(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
