<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\AssignNoteTypeRequest;
use App\Http\Requests\Note\DeleteNoteRequest;
use App\Http\Requests\Note\IndexNoteRequest;
use App\Http\Requests\Note\ShowNoteRequest;
use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Services\NoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $noteService) {}

    public function index(IndexNoteRequest $request)
    {
        try {
            $notes = $this->noteService->index($request->validated());

            return $this->respondSuccess($notes, 'Lista de notas exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao listar notas: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function show(ShowNoteRequest $request)
    {
        try {
            $note = $this->noteService->show($request->validated());

            return $this->respondSuccess($note, 'Nota exibida com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao exibir nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function store(StoreNoteRequest $request)
    {
        try {
            $note = $this->noteService->store($request->validated());

            return $this->respondSuccess($note, 'Nota criada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao criar nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function update(UpdateNoteRequest $request)
    {
        try {
            $note = $this->noteService->update($request->validated());

            return $this->respondSuccess($note, 'Nota atualizada com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atualizar nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function assignType(AssignNoteTypeRequest $request)
    {
        try {
            $note = $this->noteService->assignType($request->validated());

            return $this->respondSuccess($note, 'Tipos de nota atribuídos com sucesso!');
        } catch (ModelNotFoundException $e) {
            return $this->respondError('Nota não encontrada.', null, 404);
        } catch (\Exception $e) {
            return $this->respondError('Erro ao atribuir tipos de nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }

    public function delete(DeleteNoteRequest $request)
    {
        try {
            $this->noteService->delete($request->validated());

            return $this->respondSuccess(null, 'Nota excluída com sucesso!');
        } catch (\Exception $e) {
            return $this->respondError('Erro ao excluir nota: '.$e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
}
