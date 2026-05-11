<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\AssignTaskTipRequest;
use App\Http\Requests\Task\CompleteTaskRequest;
use App\Http\Requests\Task\DeleteTaskRequest;
use App\Http\Requests\Task\EndTaskViewSessionRequest;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\PostponeTaskRequest;
use App\Http\Requests\Task\RecordTaskViewRequest;
use App\Http\Requests\Task\RestoreTaskRequest;
use App\Http\Requests\Task\ShowTaskRequest;
use App\Http\Requests\Task\StartTaskViewSessionRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Services\SoftDeleteUndoService;
use App\Services\TaskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly SoftDeleteUndoService $softDeleteUndoService,
    ) {}

    public function index(IndexTaskRequest $request)
    {
        try {
            $tasks = $this->taskService->index($request->validated());

            return $this->respondSuccess($tasks, 'Lista de tarefas exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar tarefas: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function show(ShowTaskRequest $request)
    {
        try {
            $task = $this->taskService->show($request->validated());

            return $this->respondSuccess($task, 'Tarefa exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir tarefa: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function store(StoreTaskRequest $request)
    {
        try {
            $task = $this->taskService->store($request->validated());

            return $this->respondSuccess($task, 'Tarefa criada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar tarefa: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function update(UpdateTaskRequest $request)
    {
        try {
            $task = $this->taskService->update($request->validated());

            return $this->respondSuccess($task, 'Tarefa atualizada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atualizar tarefa: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function assignType(AssignTaskTipRequest $request)
    {
        try {
            $task = $this->taskService->assignType($request->validated());

            return $this->respondSuccess($task, 'Dicas atribuídas à tarefa com sucesso!');
        } catch (ModelNotFoundException $e) {
            return $this->respondError('Tarefa não encontrada.', null, 404);
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atribuir dicas: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function delete(DeleteTaskRequest $request)
    {
        try {
            $task = $this->taskService->delete($request->validated());

            return $this->respondSuccess($task, 'Tarefa excluída com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir tarefa: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function restore(RestoreTaskRequest $request)
    {
        try {
            $task = $this->softDeleteUndoService->restoreTask($request->validated()['id']);

            return $this->respondSuccess($task, 'Tarefa recuperada com sucesso!');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->respondError('Erro ao recuperar tarefa: '.$e->getMessage(), null, $e->getCode() ?: 400);
        }
    }

    public function postpone(PostponeTaskRequest $request)
    {
        try {
            $task = $this->taskService->postpone($request->validated());

            return $this->respondSuccess($task, 'Tarefa adiada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao adiar tarefa: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function complete(CompleteTaskRequest $request)
    {
        try {
            $task = $this->taskService->complete($request->validated());

            return $this->respondSuccess($task, 'Tarefa concluída com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao concluir tarefa: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function recordView(RecordTaskViewRequest $request)
    {
        try {
            $task = $this->taskService->recordView($request->validated());

            return $this->respondSuccess($task, 'Visualização da tarefa registrada com sucesso!');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->respondError('Erro ao registrar visualização: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function startViewSession(StartTaskViewSessionRequest $request)
    {
        try {
            $payload = $this->taskService->startViewSession($request->validated());

            return $this->respondSuccess([
                'session_id' => $payload['session_id'],
                'started_at' => $payload['started_at']->toIso8601String(),
                'task' => $payload['task'],
            ], 'Sessão de visualização iniciada.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->respondError('Erro ao iniciar sessão de visualização: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function endViewSession(EndTaskViewSessionRequest $request)
    {
        try {
            $payload = $this->taskService->endViewSession($request->validated());

            return $this->respondSuccess([
                'session_id' => $payload['session_id'],
                'duration_seconds' => $payload['duration_seconds'],
                'task' => $payload['task'],
            ], 'Sessão de visualização encerrada.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->respondError('Erro ao encerrar sessão de visualização: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
}
