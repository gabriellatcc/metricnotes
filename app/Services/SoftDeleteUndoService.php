<?php

namespace App\Services;

use App\Http\Resources\Note\NoteResource;
use App\Http\Resources\Task\TaskResource;
use App\Models\Note;
use App\Models\Task;
use Exception;
use Illuminate\Support\Facades\Gate;

class SoftDeleteUndoService
{
    /**
     * Recupera uma tarefa após exclusão lógica (soft delete).
     *
     * @throws Exception quando não autenticado (401), recurso não encontrado (404)
     *                   ou modelo não está excluído (422); AuthorizationException quando policy nega restore
     */
    public function restoreTask(string $taskId): TaskResource
    {
        $user = auth('api')->user();

        if (! $user) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $task = Task::withTrashed()
            ->where('user_id', $user->id)
            ->whereKey($taskId)
            ->first();

        if (! $task) {
            throw new Exception('Tarefa não encontrada.', 404);
        }

        if (! $task->trashed()) {
            throw new Exception('Esta tarefa não está excluída ou já foi recuperada.', 422);
        }

        Gate::authorize('restore', $task);

        $task->restore();

        return new TaskResource($task->fresh()->load('tips'));
    }

    /**
     * Recupera uma nota após exclusão lógica (soft delete).
     *
     * @throws Exception quando não autenticado (401), recurso não encontrado (404)
     *                   ou modelo não está excluído (422); AuthorizationException quando policy nega restore
     */
    public function restoreNote(string $noteId): NoteResource
    {
        $user = auth('api')->user();

        if (! $user) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $note = Note::withTrashed()
            ->where('user_id', $user->id)
            ->whereKey($noteId)
            ->first();

        if (! $note) {
            throw new Exception('Nota não encontrada.', 404);
        }

        if (! $note->trashed()) {
            throw new Exception('Esta nota não está excluída ou já foi recuperada.', 422);
        }

        Gate::authorize('restore', $note);

        $note->restore();

        return new NoteResource($note->fresh()->load(['tips', 'user']));
    }
}
