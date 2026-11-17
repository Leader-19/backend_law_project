<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class RegistrationCode extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $expiresInMinutes,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your SPRITUP verification code')
            ->greeting('Verify your email')
            ->line('Enter this code to finish creating your SPRITUP account:')
            ->line(new HtmlString(
                '<div style="font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;margin:24px 0;">'.$this->code.'</div>'
            ))
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not request this, you can safely ignore this email.');
    }
}
