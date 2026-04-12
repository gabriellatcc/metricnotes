<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\DestroyUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(IndexUserRequest $request)
    {
        try {
            Gate::authorize('index', User::class);

            $users = $this->userService->index($request->validated());

            return $this->respondSuccess($users,'Lista de usuários exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar usuários: ' . $e->getMessage(), null, $e->getCode());
        }
    }

    public function show(ShowUserRequest $request)
    {
        try {
            Gate::authorize('show', User::class);

            $user = $this->userService->show($request->validated());

            return $this->respondSuccess($user,'Usuário exibido com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir usuário: ' . $e->getMessage(), null, $e->getCode());
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {

            $user = $this->userService->store($request->validated());

            return $this->respondSuccess($user,'Usuário criado com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar usuário: ' . $e->getMessage(), null, $e->getCode());
        }
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            Gate::authorize('update', User::class);

            $user = $this->userService->update($request->validated());

            return $this->respondSuccess($user,'Usuário atualizado com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar usuário: ' . $e->getMessage(), null, $e->getCode());
        }


    }

    public function delete(DestroyUserRequest $request)
    {
        try {
            Gate::authorize('delete', User::class);

            $user = $this->userService->delete($request->validated());

            return $this->respondSuccess($user,'Usuário excluído com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir usuário: ' . $e->getMessage(), null, $e->getCode());
        }
    }
}
