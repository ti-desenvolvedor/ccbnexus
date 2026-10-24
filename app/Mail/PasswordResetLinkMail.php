<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PasswordResetLinkMail extends Mailable
{
    public function __construct(
        public string $email,
        public string $url,
        public int $minutes,
    ) {}

    public function build(): self
    {
        return $this
            ->to($this->email)
            ->subject('Redefinir senha no CCB Nexus')
            ->text('mail.password-reset-text');
    }
}
