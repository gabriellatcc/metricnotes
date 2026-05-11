<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png',
                'max:5120',
                'dimensions:max_width=500,max_height=500',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID do Usuário',
            'avatar' => 'Foto de perfil',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.mimes' => 'A foto de perfil deve ser um arquivo JPG ou PNG.',
            'avatar.dimensions' => 'A foto de perfil deve ter no máximo 500x500 pixels.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
