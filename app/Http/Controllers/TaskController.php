<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\AssignTaskTypeRequest;
use App\Http\Requests\Task\DeleteTaskRequest;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\ShowTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(IndexTaskRequest $request)
    {
        try {
            $tasks = $this->taskService->index($request->validated());
            return $this->respondSuccess($tasks, 'Lista de tarefas exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar tarefas: ' . $e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function show(ShowTaskRequest $request)
    {
        try {
            $task = $this->taskService->show($request->validated());
            return $this->respondSuccess($task, 'Tarefa exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir tarefa: ' . $e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function store(StoreTaskRequest $request)
    {
        try {
            $task = $this->taskService->store($request->validated());
            return $this->respondSuccess($task, 'Tarefa criada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar tarefa: ' . $e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function update(UpdateTaskRequest $request)
    {
        try {
            $task = $this->taskService->update($request->validated());
            return $this->respondSuccess($task, 'Tarefa atualizada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atualizar tarefa: ' . $e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function assignType(AssignTaskTypeRequest $request)
    {
        try {
            $task = $this->taskService->assignType($request->validated());
            return $this->respondSuccess($task, 'Tipos de tarefa atribuídos à tarefa com sucesso!');
        } catch (ModelNotFoundException $e) {
            return $this->respondError('Tarefa não encontrada.', null, 404);
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atribuir tipo de tarefa: ' . $e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function delete(DeleteTaskRequest $request)
    {
        try {
            $task = $this->taskService->delete($request->validated());
            return $this->respondSuccess($task, 'Tarefa excluída com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir tarefa: ' . $e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
}