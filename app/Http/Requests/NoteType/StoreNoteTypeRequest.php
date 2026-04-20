<?php

namespace App\Http\Requests\NoteType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nome do tipo de nota',
            'color' => 'cor do tipo de nota',
        ];
    }
}
