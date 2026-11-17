<?php

use App\Models\User;
use App\Notifications\RegistrationCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'Normal']);
});

function registerPending(string $email = 'newuser@example.com'): array
{
    Notification::fake();

    $response = test()->postJson('/api/register', [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['registration_token', 'expires_in']);

    $payload = json_decode(Crypt::decryptString($response->json('registration_token')), true);

    return [$response, $payload];
}

test('registering does not create a user and sends only a code by email', function () {
    [$response, $payload] = registerPending();

    expect(User::where('email', 'newuser@example.com')->exists())->toBeFalse();
    expect($payload['code'])->toMatch('/^\d{6}$/');

    Notification::assertSentOnDemand(RegistrationCode::class);
});

test('verifying with the correct code creates the user and logs them in', function () {
    [, $payload] = registerPending();

    $response = $this->postJson('/api/register/verify', [
        'registration_token' => Crypt::encryptString(json_encode($payload)),
        'code' => $payload['code'],
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['token', 'user']);

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeTrue();
});

test('verifying with the wrong code is rejected and no user is created', function () {
    [, $payload] = registerPending();

    $response = $this->postJson('/api/register/verify', [
        'registration_token' => Crypt::encryptString(json_encode($payload)),
        'code' => '000000',
    ]);

    $response->assertUnprocessable();
    expect(User::where('email', 'newuser@example.com')->exists())->toBeFalse();
});

test('verifying after the code has expired is rejected', function () {
    [, $payload] = registerPending();

    $payload['expires_at'] = now()->subMinute()->toIso8601String();

    $response = $this->postJson('/api/register/verify', [
        'registration_token' => Crypt::encryptString(json_encode($payload)),
        'code' => $payload['code'],
    ]);

    $response->assertUnprocessable();
    expect(User::where('email', 'newuser@example.com')->exists())->toBeFalse();
});

test('a tampered registration token is rejected', function () {
    $response = $this->postJson('/api/register/verify', [
        'registration_token' => 'not-a-real-token',
        'code' => '123456',
    ]);

    $response->assertUnprocessable();
});

test('verifying twice does not create a duplicate account', function () {
    [, $payload] = registerPending();
    $token = Crypt::encryptString(json_encode($payload));

    $this->postJson('/api/register/verify', ['registration_token' => $token, 'code' => $payload['code']])
        ->assertCreated();

    $response = $this->postJson('/api/register/verify', ['registration_token' => $token, 'code' => $payload['code']]);

    $response->assertUnprocessable();
    expect(User::where('email', 'newuser@example.com')->count())->toBe(1);
});
