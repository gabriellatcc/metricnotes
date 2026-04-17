<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:tasks,id'],
            'task_type_ids' => ['required', 'array'],
            'task_type_ids.*' => ['uuid', 'distinct', 'exists:task_types,id'],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'ID da tarefa',
            'task_type_ids' => 'tipos de tarefa',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
