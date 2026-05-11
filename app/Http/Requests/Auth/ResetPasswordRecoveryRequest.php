<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reset_session_id' => ['required', 'uuid'],
            'reset_secret' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reset_session_id' => 'Sessão de recuperação',
            'reset_secret' => 'Credencial da sessão',
            'password' => 'Nova senha',
        ];
    }
}
