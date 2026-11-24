<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserOnboardingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $sectorNames
     */
    public function __construct(
        public User $user,
        public string $roleName,
        public ?string $temporaryPassword = null,
        public array $sectorNames = [],
        public bool $isPasswordReset = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isPasswordReset
            ? '[BuinSeguro MiniCAD] Nuevas credenciales de acceso a tu cuenta'
            : '[BuinSeguro MiniCAD] Bienvenido/a — Datos de acceso a tu cuenta';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-onboarding',
            with: [
                'user' => $this->user,
                'roleName' => $this->roleName,
                'temporaryPassword' => $this->temporaryPassword,
                'sectorNames' => $this->sectorNames,
                'isPasswordReset' => $this->isPasswordReset,
                'loginUrl' => route('login'),
            ],
        );
    }
}
