<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskTipRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:tasks,id'],
            'tip_ids' => ['required', 'array'],
            'tip_ids.*' => ['uuid', 'distinct', 'exists:tips,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID da tarefa',
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
