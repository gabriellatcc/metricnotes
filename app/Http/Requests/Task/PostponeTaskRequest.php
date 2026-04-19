<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class PostponeTaskRequest extends FormRequest
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
            'current_due_date' => ['required', 'date'],
        ];
    }

    public function attributes()
    {
        return[
            'id'=>'ID da tarefa'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}