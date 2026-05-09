<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código para redefinir senha — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.password-recovery-otp',
            with: [
                'code' => $this->code,
                'appName' => config('app.name'),
            ],
        );
    }
}
