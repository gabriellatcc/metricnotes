<?php

namespace App\Http\Requests\Note;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
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
            'tip_ids' => ['sometimes', 'array'],
            'tip_ids.*' => ['uuid', 'distinct', 'exists:tips,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'tip_ids' => 'dicas',
            'title' => 'título',
            'body' => 'conteúdo',
        ];
    }

    /**
     * Merge JSON from the raw body into validated input (fixes wrong Content-Type, UTF-8 BOM, etc.).
     */
    public function validationData(): array
    {
        $data = parent::validationData();

        $raw = $this->getContent();
        if ($raw !== '') {
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
            $trimmed = ltrim($raw);
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = array_merge($data, $decoded);
                }
            }
        }

        if (array_key_exists('titulo', $data) && (! array_key_exists('title', $data) || $data['title'] === null || $data['title'] === '')) {
            $data['title'] = $data['titulo'];
        }

        if (array_key_exists('conteudo', $data) && ! array_key_exists('body', $data)) {
            $data['body'] = $data['conteudo'];
        }

        return $data;
    }
}
