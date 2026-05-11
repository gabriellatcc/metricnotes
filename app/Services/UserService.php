<?php

namespace App\Services;

use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{
    public function index(array $data): UserCollection
    {
        $userLogado = JWTAuth::parseToken()->authenticate();

        if (! $userLogado) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $perPage = (int) ($data['per_page'] ?? 15);
        $search = $data['search'] ?? null;

        $usersQuery = User::query()->latest();

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! $userLogado->is_admin) {
            $usersQuery->whereKey($userLogado->id);
        }

        $users = $usersQuery->paginate($perPage);

        return new UserCollection($users);
    }

    public function show(array $data): UserResource
    {
        $user = User::find($data['id']);

        if (! $user) {
            throw new Exception('Usuário não encontrado', 404);
        }

        Gate::authorize('show', $user);

        return new UserResource($user);
    }

    public function store(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = auth('api')->login($user);

        return [
            'user' => new UserResource($user),
            'authorization' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ];
    }

    public function update(array $data): UserResource
    {
        $user = User::find($data['id']);

        if (! $user) {
            throw new Exception('Usuário não encontrado', 404);
        }

        Gate::authorize('update', $user);

        $user->update($data);

        return new UserResource($user->refresh());
    }

    public function uploadAvatar(array $data): UserResource
    {
        $user = User::find($data['id']);

        if (! $user) {
            throw new Exception('Usuário não encontrado', 404);
        }

        Gate::authorize('update', $user);

        /** @var UploadedFile $file */
        $file = $data['avatar'];

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $basename = Str::uuid()->toString().'.'.strtolower($extension);
        $path = $file->storeAs('avatars/'.$user->id, $basename, 'public');

        $user->update(['avatar_path' => $path]);

        return new UserResource($user->refresh());
    }

    public function changePassword(array $data): UserResource
    {
        $user = User::find($data['id']);

        if (! $user) {
            throw new Exception('Usuário não encontrado', 404);
        }

        Gate::authorize('update', $user);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw new Exception('A senha atual está incorreta.', 422);
        }

        $user->update(['password' => $data['password']]);

        return new UserResource($user->refresh());
    }

    public function delete(array $data): bool
    {
        $user = User::find($data['id']);

        if (! $user) {
            throw new Exception('Usuário não encontrado', 404);
        }

        Gate::authorize('delete', $user);

        return $user->delete();
    }
}
