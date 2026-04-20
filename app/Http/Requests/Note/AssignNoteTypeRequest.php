<?php

namespace App\Http\Requests\Note;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignNoteTypeRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:notes,id'],
            'note_type_ids' => ['required', 'array'],
            'note_type_ids.*' => ['uuid', 'distinct', 'exists:note_types,id'],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'ID da nota',
            'note_type_ids' => 'tipos de nota',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
