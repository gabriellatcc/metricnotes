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
            'task_type_id' => ['nullable', 'integer', 'exists:task_types,id'],
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
            'task_type_id'=>'ID do tipo de tarefa',
            'name'=>'nome da tarefa',
            'description'=>'descrição da tarefa',
            'status'=>'status da tarefa',
            'priority'=>'prioridade da tarefa',
            'due_date' => 'Data de vencimento',
        ];
    }
}