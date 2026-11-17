<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyApiEmail extends VerifyEmail
{
    /**
     * Build the verification URL against the API's own signed route
     * (api.verification.verify) rather than Fortify's "verification.verify",
     * which requires a web session the Bearer-token SPA never has.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
