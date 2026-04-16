<?php

namespace App\Services;

use App\Http\Resources\Task\TaskCollection;
use App\Http\Resources\Task\TaskResource;
use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Exception;

class TaskService
{
    public function index(array $data): TaskCollection
    {
        $user = auth('api')->user();

        if (!$user) {
            throw new Exception('Usuário não autenticado.', 401);
        }

        $tasks = Task::query()
            ->with(['taskType'])
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
                $query->where('task_type_id', $typeId);
            })
            ->latest()
            ->paginate($data['per_page'] ?? 15, ['*'], 'page', $data['page'] ?? 1);

        return new TaskCollection($tasks);
    }

    public function show(array $data): TaskResource
    {
        $task = Task::with(['taskType'])->find($data['id']);

        if (!$task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        // Se der erro aqui, verifique a TaskPolicy.php
        Gate::authorize('show', $task);

        return new TaskResource($task);
    }

    public function store(array $data): TaskResource
    {
        $data['user_id'] = auth('api')->id();

        if (isset($data['due_date'])) {
            $data['original_due_date'] = $data['due_date'];
            $data['current_due_date'] = $data['due_date'];
        }

        $task = Task::create($data);

        return new TaskResource($task->load('taskType'));
    }

    public function update(array $data): TaskResource
    {
        $task = Task::find($data['id']);

        if (!$task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('update', $task);

        $task->update($data);

        return new TaskResource($task->load('taskType'));
    }

    public function delete(array $data): bool
    {
        $task = Task::find($data['id']);

        if (!$task) {
            throw new Exception('Tarefa não encontrada', 404);
        }

        Gate::authorize('delete', $task);

        return $task->delete();
    }
}