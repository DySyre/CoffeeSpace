<?php

use App\Models\User;
use App\Notifications\ActivityEmailNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;

test('CoffeeSpace emails contain an inline SVG logo and no image attachments', function (string $type) {
    config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array'], 'mail.from.address' => 'hello@example.test', 'mail.from.name' => 'CoffeeSpace']);
    $user = User::factory()->make(['id' => 1, 'name' => 'Coffee Friend', 'email' => 'recipient@example.test']);
    $notification = match ($type) {
        'verify' => new VerifyEmailNotification,
        'reset' => new ResetPasswordNotification('test-reset-token'),
        default => new ActivityEmailNotification(strtoupper($type), 'A coffee update', 'Another Creator', 'A cafe worth visiting', 'View update', 'https://example.test/update'),
    };
    $transport = Mail::mailer('array')->getSymfonyTransport();

    // The real mail channel compiles the MIME message; array transport never contacts SMTP.
    Notification::sendNow($user, $notification);
    $email = $transport->messages()->sole()->getOriginalMessage();

    expect($email)->toBeInstanceOf(Email::class);
    expect($email->getAttachments())->toBeEmpty();
    expect($email->getHtmlBody())->toContain('<svg', 'CoffeeSpace coffee cup logo', 'YOUR DAILY ORBIT')
        ->not->toContain('<img', 'cid:', 'data:image/');
    expect($email->toString())->not->toContain('Content-ID:', 'Content-Disposition: attachment', 'Content-Type: image/');
})->with(['verify', 'reset', 'message', 'comment', 'save']);
