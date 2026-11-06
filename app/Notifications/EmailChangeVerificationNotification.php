<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class EmailChangeVerificationNotification extends VerifyEmailNotification
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new CoffeeSpace email')
            ->greeting('Confirm your new email address')
            ->line('You asked to change the email address on your CoffeeSpace account.')
            ->action('Verify new email address', $this->verificationUrl($notifiable))
            ->line('Your current email remains on your account until you confirm this address.')
            ->line('This link expires in '.config('auth.verification.expire').' minutes. If you did not request this change, you can ignore this email.');
    }
}
