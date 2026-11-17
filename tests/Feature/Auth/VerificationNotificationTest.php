<?php

use App\Models\User;
use App\Notifications\VerifyApiEmail;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    // User::sendEmailVerificationNotification() is overridden to always send
    // VerifyApiEmail (links to the signed API route, not the session-guarded
    // Fortify one) so the same verification link works regardless of which
    // path triggered it.
    Notification::assertSentTo($user, VerifyApiEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});
