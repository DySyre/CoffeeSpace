<?php

use App\Auth\PendingEmailVerification;
use App\Events\UserActivityChanged;
use App\Models\User;
use App\Notifications\ActivityEmailNotification;
use App\Notifications\ActivityNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\ActivityNotifier;
use App\Services\EmailDeliveryLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('verification emails are limited to one per address every five minutes', function () {
    Notification::fake();
    $limiter = app(EmailDeliveryLimiter::class);
    $email = 'coffee.friend@example.test';
    $firstRecipient = new PendingEmailVerification('first', 'Coffee Friend', $email);
    $secondRecipient = new PendingEmailVerification('second', 'Coffee Friend', $email);

    expect($limiter->sendVerification($firstRecipient, $email, new VerifyEmailNotification))->toBeTrue()
        ->and($limiter->sendVerification($secondRecipient, $email, new VerifyEmailNotification))->toBeFalse();

    Notification::assertSentToTimes($firstRecipient, VerifyEmailNotification::class, 1);
    Notification::assertSentToTimes($secondRecipient, VerifyEmailNotification::class, 0);

    $this->travel(5)->minutes();

    expect($limiter->sendVerification($secondRecipient, $email, new VerifyEmailNotification))->toBeTrue();
    Notification::assertSentToTimes($secondRecipient, VerifyEmailNotification::class, 1);
});

test('activity emails are limited per recipient and category every ten minutes', function () {
    Notification::fake();
    Event::fake([UserActivityChanged::class]);

    $recipient = User::factory()->create();
    $recipient->notificationSettings()->updateOrCreate([], ['notify_comments_by_email' => true, 'notify_saves_by_email' => true]);
    $notifier = app(ActivityNotifier::class);

    $notifier->send($recipient, 'COMMENT', 'A comment', 'Coffee Friend', 'Your story', 'View comment', '/story');
    $notifier->send($recipient, 'COMMENT', 'A comment', 'Coffee Friend', 'Your story', 'View comment', '/story');
    $notifier->send($recipient, 'SAVED', 'A save', 'Coffee Friend', 'Your story', 'View save', '/story');

    Notification::assertSentToTimes($recipient, ActivityNotification::class, 3);
    Notification::assertSentToTimes($recipient, ActivityEmailNotification::class, 2);

    $this->travel(10)->minutes();

    $notifier->send($recipient, 'COMMENT', 'A comment', 'Coffee Friend', 'Your story', 'View comment', '/story');

    Notification::assertSentToTimes($recipient, ActivityEmailNotification::class, 3);
});
