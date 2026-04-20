<?php

namespace App\Http\Requests\Note;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note_type_ids' => ['sometimes', 'array'],
            'note_type_ids.*' => ['uuid', 'distinct', 'exists:note_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'note_type_ids' => 'tipos de nota',
            'title' => 'título',
            'body' => 'conteúdo',
        ];
    }
}
