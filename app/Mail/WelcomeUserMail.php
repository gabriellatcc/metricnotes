<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmação de conta e boas-vindas — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-user',
            with: [
                'recipientName' => $this->user->name,
                'appName' => config('app.name'),
            ],
        );
    }
}
