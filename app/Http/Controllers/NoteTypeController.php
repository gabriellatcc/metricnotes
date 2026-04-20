<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteType\DeleteNoteTypeRequest;
use App\Http\Requests\NoteType\IndexNoteTypeRequest;
use App\Http\Requests\NoteType\ShowNoteTypeRequest;
use App\Http\Requests\NoteType\StoreNoteTypeRequest;
use App\Http\Requests\NoteType\UpdateNoteTypeRequest;
use App\Services\NoteTypeService;

class NoteTypeController extends Controller
{
    public function __construct(private readonly NoteTypeService $noteTypeService) {}

    public function index(IndexNoteTypeRequest $request)
    {
        try {
            $items = $this->noteTypeService->index($request->validated());

            return $this->respondSuccess($items, 'Lista de tipos de nota exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar tipos de nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function show(ShowNoteTypeRequest $request)
    {
        try {
            $item = $this->noteTypeService->show($request->validated());

            return $this->respondSuccess($item, 'Tipo de nota exibido com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir tipo de nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function store(StoreNoteTypeRequest $request)
    {
        try {
            $item = $this->noteTypeService->store($request->validated());

            return $this->respondSuccess($item, 'Tipo de nota criado com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar tipo de nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function update(UpdateNoteTypeRequest $request)
    {
        try {
            $item = $this->noteTypeService->update($request->validated());

            return $this->respondSuccess($item, 'Tipo de nota atualizado com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atualizar tipo de nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function delete(DeleteNoteTypeRequest $request)
    {
        try {
            $this->noteTypeService->delete($request->validated());

            return $this->respondSuccess(null, 'Tipo de nota excluído com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir tipo de nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
}
