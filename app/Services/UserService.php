<?php

namespace App\Services;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index(array $data): UserCollection
    {
        $userId = (int) $data['user_id'];
        $isAdmin = (bool) $data['is_admin'];
        $perPage = (int) $data['per_page'];

        $usersQuery = User::query()->latest();

        if (! $isAdmin) {
            $usersQuery->whereKey($userId);
        }

        $users = $usersQuery->paginate($perPage);

        return new UserCollection($users);
    }

    public function show(array $data): UserResource
    {
        $user = User::findOrFail($data['id']);
        return new UserResource($user);
    }

    public function store(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = auth('api')->login($user);

        return [
            'user' => new UserResource($user),
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]
        ];
    }

    public function update(array $data): UserResource
    {
        $user = User::findOrFail($data['id']);
        if (! $user) {
            throw new \Exception('Usuário não encontrado', 404);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return new UserResource($user->refresh());
    }

    public function delete(array $data): void
    {
        $user = User::findOrFail($data['id']);

        $user->delete();
    }
}
