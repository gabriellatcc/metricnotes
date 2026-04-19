<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'task_type_ids' => ['sometimes', 'array'],
            'task_type_ids.*' => ['uuid', 'distinct', 'exists:task_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,in_progress'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];

    }

    public function attributes()
    {
        return[
            'task_type_ids'=>'tipos de tarefa',
            'name'=>'nome da tarefa',
            'description'=>'descrição da tarefa',
            'status'=>'status da tarefa',
            'priority'=>'prioridade da tarefa',
            'due_date' => 'Data de vencimento',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.date' => 'A :attribute deve ser uma data válida.',
            'due_date.after_or_equal' => 'A :attribute deve ser hoje ou uma data futura.',
        ];
    }
}