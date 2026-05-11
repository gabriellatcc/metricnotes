<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class MarkTaskDueNotificationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'task' => ['required', 'uuid', 'exists:tasks,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'task' => $this->route('task'),
        ]);
    }
}
