<?php

namespace App\Services;

use App\Http\Resources\Tip\TipCollection;
use App\Http\Resources\Tip\TipResource;
use App\Models\Tip;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TipService
{
    public function index(array $data): TipCollection
    {
        $perPage = (int) ($data['per_page'] ?? 15);
        $search = $data['search'] ?? null;
        $userId = Auth::id();

        if (! $userId) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $query = Tip::query()
            ->with('user')
            ->where('user_id', $userId)
            ->latest();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $tips = $query->paginate($perPage);

        return new TipCollection($tips);
    }

    public function show(array $data): TipResource
    {
        $tip = Tip::with('user')->find($data['id']);

        if (! $tip) {
            throw new Exception('Dica não encontrada', 404);
        }

        Gate::authorize('show', $tip);

        return new TipResource($tip);
    }

    public function store(array $data): TipResource
    {
        $userId = Auth::id();

        if (! $userId) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $tip = new Tip;
        $tip->user_id = $userId;
        $tip->name = $data['name'];
        $tip->color = $data['color'] ?? null;
        $tip->save();
        $tip->load('user');

        return new TipResource($tip);
    }

    public function update(array $data): TipResource
    {
        $tip = Tip::find($data['id']);

        if (! $tip) {
            throw new Exception('Dica não encontrada', 404);
        }

        Gate::authorize('update', $tip);

        $tip->update([
            'name' => $data['name'] ?? $tip->name,
            'color' => $data['color'] ?? $tip->color,
        ]);

        return new TipResource($tip->refresh()->load('user'));
    }

    public function delete(array $data): bool
    {
        $tip = Tip::find($data['id']);

        if (! $tip) {
            throw new Exception('Dica não encontrada', 404);
        }

        Gate::authorize('delete', $tip);

        return $tip->delete();
    }
}
