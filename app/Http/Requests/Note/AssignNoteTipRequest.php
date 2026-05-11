<?php

namespace App\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;

class AssignNoteTipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:notes,id'],
            'tip_ids' => ['required', 'array'],
            'tip_ids.*' => ['uuid', 'distinct', 'exists:tips,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID da nota',
            'tip_ids' => 'dicas',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
