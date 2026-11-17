<?php

use App\Models\User;
use App\Notifications\VerifyApiEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('an unverified user cannot log in', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertForbidden();
    $response->assertJson(['requires_verification' => true]);
});

test('clicking the verification link verifies the email and allows login', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'api.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $loginResponse = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertOk();
    $loginResponse->assertJsonStructure(['token', 'user']);
});

test('an invalid verification hash does not verify the email', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('someone-else@example.com')]
    );

    $response = $this->get($url);

    expect($response->headers->get('Location'))->toContain('verified=0');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('an expired or tampered verification link is rejected by the signed middleware', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'api.verification.verify',
        now()->subMinutes(5), // already expired
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $this->get($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('resending the verification email does not reveal whether the account exists', function () {
    $response = $this->postJson('/api/email/resend-verification', [
        'email' => 'does-not-exist@example.com',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'success']);
});

test('resending the verification email works for an unverified account', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->postJson('/api/email/resend-verification', ['email' => $user->email]);

    $response->assertOk();
    Notification::assertSentTo($user, VerifyApiEmail::class);
});

test('resending does not re-send for an already-verified account', function () {
    Notification::fake();

    $user = User::factory()->create(); // verified by default

    $this->postJson('/api/email/resend-verification', ['email' => $user->email]);

    Notification::assertNotSentTo($user, VerifyApiEmail::class);
});
