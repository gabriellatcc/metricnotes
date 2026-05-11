<?php

namespace App\Http\Requests\Task;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EndTaskViewSessionRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:tasks,id'],
            'session_id' => [
                'required',
                'uuid',
                Rule::exists('task_view_sessions', 'id')->where(
                    fn ($query) => $query->where('task_id', $this->route('id'))
                        ->where('user_id', auth('api')->id())
                ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID da tarefa',
            'session_id' => 'ID da sessão de visualização',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
