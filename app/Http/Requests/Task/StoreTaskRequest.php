<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\Task\Concerns\NormalizesTaskDueDatetime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'tip_ids' => ['sometimes', 'array'],
            'tip_ids.*' => ['uuid', 'distinct', 'exists:tips,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,in_progress'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            /**
             * Prazo máximo para conclusão (data/h).
             * Envie só `due_date` (vários formatos) ou datetime completo (`Y-m-d H:i:s`, ISO 8601),
             * opcionalmente com `due_time` quando `due_date` for apenas calendário.
             */
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            /** Aplicado apenas se `due_date` for apenas data (`dd/mm/aaaa` ou `Y-m-d`). Opcional formatos `H:mm` ou `HH:mm:ss`. */
            'due_time' => ['sometimes', 'nullable', 'regex:/^\d{1,2}:\d{2}(?::\d{2})?$/'],
        ];

    }

    protected function prepareForValidation(): void
    {
        $this->normalizeTaskDueDatetimeField('due_date', 'due_time');
    }

    /**
     * Não persistir campo auxiliar quando retorna o pacote inteiro validado.
     *
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function validated($key = null, $default = null): mixed
    {
        if (func_num_args() === 0) {
            $data = parent::validated();
            unset($data['due_time']);

            return $data;
        }

        return parent::validated($key, $default);
    }

    public function attributes()
    {
        return [
            'tip_ids' => 'tipos',
            'name' => 'nome da tarefa',
            'description' => 'descrição da tarefa',
            'status' => 'status da tarefa',
            'priority' => 'prioridade da tarefa',
            'due_date' => 'Data de vencimento',
            'due_time' => 'Hora de vencimento',
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
            'due_time.regex' => 'O campo hora deve estar no formato H:mm ou HH:mm:ss.',
        ];
    }
}
