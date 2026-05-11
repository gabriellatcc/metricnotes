<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\User\UserResource;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class AuthService
{
    public function login(array $data): array
    {
        if (! $token = auth('api')->attempt($data)) {
            $message = 'Credenciais inválidas';
            if (config('app.debug')) {
                $message .= ' (confira e-mail/senha; em banco novo use: php artisan db:seed --class=AdminUserSeeder).';
            }

            throw new Exception($message, 401);
        }
    
        $user = auth('api')->user();
    
        return [
            'user' => new UserResource($user),
            'authorization' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]
        ];
    }

    public function me(): array
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                throw new Exception('', 404);
            }

            return [
                'user' => new UserResource($user),
            ];
        } catch (Exception $e) {
            throw new Exception('', 401);
        }
    }

    public function refreshToken(array $data): array
    {
        try {
            $newAccessToken = JWTAuth::setToken($data['refresh_token'])->refresh();
            $user = JWTAuth::setToken($newAccessToken)->toUser();

            if (! $user) {
                throw new Exception('', 404);
            }

            return [
                'user' => new UserResource($user),
                'authorization' => [
                    'access_token' => $newAccessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ]
            ];
        } catch (Exception $e) {
            throw new Exception('', 401);
        }
    }
}