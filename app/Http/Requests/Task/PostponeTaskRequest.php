<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\Task\Concerns\NormalizesTaskDueDatetime;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostponeTaskRequest extends FormRequest
{
    use NormalizesTaskDueDatetime;

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
            'current_due_date' => ['required', 'date'],
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

    public function attributes(): array
    {
        return [
            'id' => 'ID da tarefa',
            'current_due_date' => 'novo prazo',
            'current_due_time' => 'hora do novo prazo',
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);

        $this->normalizeTaskDueDatetimeField('current_due_date', 'current_due_time');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $task = Task::query()->find($this->route('id'));
            if (! $task instanceof Task) {
                return;
            }

            if ($task->postponed_count >= 3) {
                $v->errors()->add(
                    'id',
                    'Esta tarefa já atingiu o limite máximo de 3 adiamentos.'
                );

                return;
            }

            if (! $this->postponeChainIsSequential($task)) {
                $v->errors()->add(
                    'id',
                    'Não é possível pular etapas nos adiamentos. Cada adiamento deve seguir o anterior; atualize a lista e tente novamente.'
                );

                return;
            }

            if ($task->qualifiesLongHorizonPostponeExemption()) {
                return;
            }

            try {
                $newDue = Carbon::parse((string) $this->input('current_due_date'));
            } catch (\Throwable) {
                return;
            }

            $plannedCompletion = $task->current_due_date ?? $task->original_due_date;

            match ($task->postponed_count) {
                0 => $this->validateFirstPostponementDueLimit($plannedCompletion, $newDue, $v),
                1 => $this->validateSecondPostponementWindow($task, $v),
                2 => $this->validateThirdPostponementWindow($task, $v),
                default => null,
            };
        });
    }

    /**
     * 3.1 — integridade da cadeia: não permitir estado incoerente (ex.: 2.º/3.º sem datas anteriores).
     */
    private function postponeChainIsSequential(Task $task): bool
    {
        $n = (int) $task->postponed_count;

        if ($n >= 1 && $task->postponed_date_1 === null) {
            return false;
        }
        if ($n >= 2 && $task->postponed_date_2 === null) {
            return false;
        }
        if ($n >= 3 && $task->postponed_date_3 === null) {
            return false;
        }

        return true;
    }

    /**
     * 3.2 — 1.º adiamento: novo prazo no máximo 4 dias após a conclusão prevista.
     */
    private function validateFirstPostponementDueLimit(mixed $plannedCompletion, Carbon $newDue, Validator $v): void
    {
        if ($plannedCompletion === null) {
            $v->errors()->add(
                'current_due_date',
                'É necessário ter uma data de conclusão prevista para aplicar as regras de adiamento.'
            );

            return;
        }

        $anchor = Carbon::parse($plannedCompletion);
        $maxInclusive = $anchor->copy()->addDays(4);

        if ($newDue->greaterThan($maxInclusive)) {
            $v->errors()->add(
                'current_due_date',
                'No 1.º adiamento, o novo prazo não pode ser mais de 4 dias após a conclusão prevista.'
            );
        }
    }

    /**
     * 3.3 — 2.º adiamento: em até 2 dias após o 1.º adiamento (momento registrado).
     */
    private function validateSecondPostponementWindow(Task $task, Validator $v): void
    {
        if ($task->postponed_date_1 === null) {
            $v->errors()->add(
                'id',
                'Não é possível registrar o 2.º adiamento sem o 1.º adiamento registrado corretamente.'
            );

            return;
        }

        $lastAllowedDay = Carbon::parse($task->postponed_date_1)->startOfDay()->addDays(2);

        if (Carbon::now()->startOfDay()->gt($lastAllowedDay)) {
            $v->errors()->add(
                'id',
                'O 2.º adiamento só pode ser feito em até 2 dias após o 1.º adiamento.'
            );
        }
    }

    /**
     * 3.4 — 3.º adiamento: em até 1 dia após o 2.º adiamento (momento registrado).
     */
    private function validateThirdPostponementWindow(Task $task, Validator $v): void
    {
        if ($task->postponed_date_2 === null) {
            $v->errors()->add(
                'id',
                'Não é possível registrar o 3.º adiamento sem o 2.º adiamento registrado corretamente.'
            );

            return;
        }

        $lastAllowedDay = Carbon::parse($task->postponed_date_2)->startOfDay()->addDays(1);

        if (Carbon::now()->startOfDay()->gt($lastAllowedDay)) {
            $v->errors()->add(
                'id',
                'O 3.º adiamento só pode ser feito em até 1 dia após o 2.º adiamento.'
            );
        }
    }
}
