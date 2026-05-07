<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\ChangeUserPasswordRequest;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UploadUserAvatarRequest;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(IndexUserRequest $request)
    {
        try {

            $users = $this->userService->index($request->validated());

            return $this->respondSuccess($users, 'Lista de usuários exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar usuários: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function show(ShowUserRequest $request)
    {
        try {
            $user = $this->userService->show($request->validated());

            return $this->respondSuccess($user, 'Usuário exibido com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir usuário: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->store($request->validated());

            return $this->respondSuccess($user, 'Usuário criado com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar usuário: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function update(UpdateUserRequest $request)
    {
        try {
            $user = $this->userService->update($request->validated());

            return $this->respondSuccess($user, 'Usuário atualizado com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atualizar usuário: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function uploadAvatar(UploadUserAvatarRequest $request)
    {
        try {
            $user = $this->userService->uploadAvatar($request->validated());

            return $this->respondSuccess($user, 'Foto de perfil atualizada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao enviar foto: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function changePassword(ChangeUserPasswordRequest $request)
    {
        try {
            $user = $this->userService->changePassword($request->validated());

            return $this->respondSuccess(['user' => $user], 'Senha alterada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao alterar senha: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function delete(DeleteUserRequest $request)
    {
        try {
            $user = $this->userService->delete($request->validated());

            return $this->respondSuccess($user, 'Usuário excluído com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir usuário: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
}
