<?php

namespace App\Observers;

use App\Mail\WelcomeUserMail;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    public function created(User $user): void
    {
        $defaultTypes = [
            ['name' => 'Trabalho', 'color' => '#FF0000'],
            ['name' => 'Estudo', 'color' => '#00FF00'],
            ['name' => 'Lazer', 'color' => '#0000FF'],
        ];

        foreach ($defaultTypes as $type) {
            Tip::create([
                'user_id' => $user->id,
                'name' => $type['name'],
                'color' => $type['color'],
            ]);
        }

        if (! config('mail.send_welcome_to_new_users')) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user));
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar e-mail de boas-vindas.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
