<?php

namespace App\Services;

use App\Http\Resources\NoteType\NoteTypeCollection;
use App\Http\Resources\NoteType\NoteTypeResource;
use App\Models\NoteType;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class NoteTypeService
{
    public function index(array $data): NoteTypeCollection
    {
        $perPage = (int) ($data['per_page'] ?? 15);
        $search = $data['search'] ?? null;
        $userId = Auth::id();

        if (! $userId) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $query = NoteType::query()
            ->with('user')
            ->where('user_id', $userId)
            ->latest();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $noteTypes = $query->paginate($perPage);

        return new NoteTypeCollection($noteTypes);
    }

    public function show(array $data): NoteTypeResource
    {
        $noteType = NoteType::with('user')->find($data['id']);

        if (! $noteType) {
            throw new Exception('Tipo de nota não encontrado', 404);
        }

        Gate::authorize('show', $noteType);

        return new NoteTypeResource($noteType);
    }

    public function store(array $data): NoteTypeResource
    {
        $userId = Auth::id();

        if (! $userId) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $noteType = new NoteType;
        $noteType->user_id = $userId;
        $noteType->name = $data['name'];
        $noteType->color = $data['color'] ?? null;
        $noteType->save();
        $noteType->load('user');

        return new NoteTypeResource($noteType);
    }

    public function update(array $data): NoteTypeResource
    {
        $noteType = NoteType::find($data['id']);

        if (! $noteType) {
            throw new Exception('Tipo de nota não encontrado', 404);
        }

        Gate::authorize('update', $noteType);

        $noteType->update([
            'name' => $data['name'] ?? $noteType->name,
            'color' => $data['color'] ?? $noteType->color,
        ]);

        return new NoteTypeResource($noteType->refresh()->load('user'));
    }

    public function delete(array $data): bool
    {
        $noteType = NoteType::find($data['id']);

        if (! $noteType) {
            throw new Exception('Tipo de nota não encontrado', 404);
        }

        Gate::authorize('delete', $noteType);

        return $noteType->delete();
    }
}
