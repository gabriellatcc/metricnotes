<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\Task\Concerns\NormalizesTaskDueDatetime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    use NormalizesTaskDueDatetime;

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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'postponed', 'canceled'])],
            /**
             * Prazo atual máximo (data/h).
             */
            'current_due_date' => ['sometimes', 'nullable', 'date'],
            'current_due_time' => ['sometimes', 'nullable', 'regex:/^\d{1,2}:\d{2}(?::\d{2})?$/'],
        ];
    }

    /**
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function validated($key = null, $default = null): mixed
    {
        if (func_num_args() === 0) {
            $data = parent::validated();
            unset($data['current_due_time']);

            return $data;
        }

        return parent::validated($key, $default);
    }

    public function attributes()
    {
        return [
            'id' => 'ID da tarefa',
            'current_due_date' => 'Prazo de conclusão',
            'current_due_time' => 'Hora do prazo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_due_time.regex' => 'A hora deve estar no formato H:mm ou HH:mm:ss.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);

        $this->normalizeTaskDueDatetimeField('current_due_date', 'current_due_time');
    }
}
