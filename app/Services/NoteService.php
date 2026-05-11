<?php

namespace App\Services;

use App\Http\Resources\Note\NoteCollection;
use App\Http\Resources\Note\NoteResource;
use App\Models\Note;
use App\Models\Tip;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NoteService
{
    public function index(array $data): NoteCollection
    {
        $user = auth('api')->user();

        if (! $user) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $notes = Note::query()
            ->with(['tips', 'user'])
            ->where('user_id', $user->id)
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->when($data['tip_id'] ?? null, function ($query, $tipId) {
                $query->whereHas('tips', fn ($q) => $q->where('tips.id', $tipId));
            })
            ->latest()
            ->paginate($data['per_page'] ?? 15, ['*'], 'page', $data['page'] ?? 1);

        return new NoteCollection($notes);
    }

    public function show(array $data): NoteResource
    {
        $note = Note::with(['tips', 'user'])->find($data['id']);

        if (! $note) {
            throw new Exception('Nota não encontrada', 404);
        }

        Gate::authorize('show', $note);

        return new NoteResource($note);
    }

    public function store(array $data): NoteResource
    {
        $tipIds = $data['tip_ids'] ?? [];
        unset($data['tip_ids']);

        $data['user_id'] = auth('api')->id();

        $note = Note::create($data);

        $syncIds = $this->tipIdsOwnedByNoteUser($note, $tipIds);
        $note->tips()->sync($syncIds);

        return new NoteResource($note->load(['tips', 'user']));
    }

    public function update(array $data): NoteResource
    {
        $note = Note::find($data['id']);

        if (! $note) {
            throw new Exception('Nota não encontrada', 404);
        }

        Gate::authorize('update', $note);

        $note->update([
            'title' => $data['title'] ?? $note->title,
            'body' => array_key_exists('body', $data) ? $data['body'] : $note->body,
        ]);

        return new NoteResource($note->load(['tips', 'user']));
    }

    public function assignType(array $data): NoteResource
    {
        $note = Note::findOrFail($data['id']);

        Gate::authorize('update', $note);

        $tipIds = $data['tip_ids'] ?? [];

        DB::transaction(function () use ($note, $tipIds) {
            $syncIds = $this->tipIdsOwnedByNoteUser($note, $tipIds);
            $note->tips()->sync($syncIds);
        });

        return new NoteResource($note->fresh()->load(['tips', 'user']));
    }

    public function delete(array $data): bool
    {
        $note = Note::find($data['id']);

        if (! $note) {
            throw new Exception('Nota não encontrada', 404);
        }

        Gate::authorize('delete', $note);

        return $note->delete();
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<int, string>
     */
    protected function tipIdsOwnedByNoteUser(Note $note, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        $validCount = Tip::query()
            ->whereIn('id', $ids)
            ->where('user_id', $note->user_id)
            ->count();

        if ($validCount !== count($ids)) {
            throw new Exception('Uma ou mais dicas são inválidas ou não pertencem ao dono da nota.', 422);
        }

        return $ids;
    }
}
