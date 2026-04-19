<?php

namespace App\Services;

use App\Http\Resources\Task\TaskCollection;
use App\Http\Resources\Task\TaskResource;
use App\Models\Task;
use App\Models\TaskType;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskService
{
    public function index(array $data): TaskCollection
    {
        $user = auth('api')->user();

        if (! $user) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $tasks = Task::query()
            ->with(['taskTypes'])
            ->where('user_id', $user->id)
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($data['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($data['task_type_id'] ?? null, function ($query, $typeId) {
                $query->whereHas('taskTypes', fn ($q) => $q->where('task_types.id', $typeId));
            })
            ->latest()
            ->paginate($data['per_page'] ?? 15, ['*'], 'page', $data['page'] ?? 1);

        return new TaskCollection($tasks);
    }

    public function show(array $data): TaskResource
    {
        $task = Task::with(['taskTypes'])->find($data['id']);

        if (! $task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('show', $task);

        return new TaskResource($task);
    }

    public function store(array $data): TaskResource
    {
        $taskTypeIds = $data['task_type_ids'] ?? [];
        unset($data['task_type_ids']);

        $data['user_id'] = auth('api')->id();

        if (isset($data['due_date'])) {
            $data['original_due_date'] = $data['due_date'];
            $data['current_due_date'] = $data['due_date'];
        }

        $task = Task::create($data);

        $syncIds = $this->taskTypeIdsOwnedByTaskUser($task, $taskTypeIds);
        $task->taskTypes()->sync($syncIds);

        return new TaskResource($task->load('taskTypes'));
    }

    public function update(array $data): TaskResource
    {
        $task = Task::find($data['id']);

        if (! $task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('update', $task);

        $task->update($data);

        return new TaskResource($task->load('taskTypes'));
    }

    public function assignType(array $data): TaskResource
    {
        $task = Task::findOrFail($data['id']);

        Gate::authorize('update', $task);

        $taskTypeIds = $data['task_type_ids'] ?? [];

        DB::transaction(function () use ($task, $taskTypeIds) {
            $syncIds = $this->taskTypeIdsOwnedByTaskUser($task, $taskTypeIds);
            $task->taskTypes()->sync($syncIds);
        });

        return new TaskResource($task->fresh()->load('taskTypes'));
    }

    public function delete(array $data): bool
    {
        $task = Task::find($data['id']);

        if (! $task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('delete', $task);

        return $task->delete();
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<int, string>
     */
    protected function taskTypeIdsOwnedByTaskUser(Task $task, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        $validCount = TaskType::query()
            ->whereIn('id', $ids)
            ->where('user_id', $task->user_id)
            ->count();

        if ($validCount !== count($ids)) {
            throw new Exception('Um ou mais tipos de tarefa são inválidos ou não pertencem ao dono da tarefa.', 422);
        }

        return $ids;
    }

    public function postpone(array $data): TaskResource
    {
        $task = Task::find($data['id']);

        if (! $task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('update', $task);

        if ($task->postponed_count >= 3) {
            throw new Exception('Esta tarefa já atingiu o limite máximo de 3 adiamentos.', 400);
        }

        $task->postponed_count += 1;

        $dateField = 'postponed_date_' . $task->postponed_count;
        $task->{$dateField} = now();

        $task->current_due_date = $data['current_due_date'];
        $task->status = 'postponed';

        $task->save();
        $task->load('taskTypes');

        return new TaskResource($task);
    }

    public function complete(array $data): TaskResource
    {
        $task = Task::find($data['id']);

        if (! $task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('update', $task);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $task->load('taskTypes');

        return new TaskResource($task);
    }
}
