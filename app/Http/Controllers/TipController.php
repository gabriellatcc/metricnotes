<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tip\DeleteTipRequest;
use App\Http\Requests\Tip\IndexTipRequest;
use App\Http\Requests\Tip\ShowTipRequest;
use App\Http\Requests\Tip\StoreTipRequest;
use App\Http\Requests\Tip\UpdateTipRequest;
use App\Services\TipService;

class TipController extends Controller
{
    public function __construct(private readonly TipService $tipService) {}

    public function index(IndexTipRequest $request)
    {
        try {
            $tips = $this->tipService->index($request->validated());

            return $this->respondSuccess($tips, 'Lista de dicas exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar dicas: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function show(ShowTipRequest $request)
    {
        try {
            $tip = $this->tipService->show($request->validated());

            return $this->respondSuccess($tip, 'Dica exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir dica: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function store(StoreTipRequest $request)
    {
        try {
            $tip = $this->tipService->store($request->validated());

            return $this->respondSuccess($tip, 'Dica criada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar dica: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function update(UpdateTipRequest $request)
    {
        try {
            $tip = $this->tipService->update($request->validated());

            return $this->respondSuccess($tip, 'Dica atualizada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atualizar dica: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function delete(DeleteTipRequest $request)
    {
        try {
            $this->tipService->delete($request->validated());

            return $this->respondSuccess(null, 'Dica excluída com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir dica: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
}
