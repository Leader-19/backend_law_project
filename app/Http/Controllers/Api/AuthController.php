<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Notifications\RegistrationCode;
use App\Services\SubscriptionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Minutes a registration code stays valid before the pending signup must be restarted. */
    private const REGISTRATION_CODE_TTL_MINUTES = 3;

    /**
     * No User row is created here. The full signup is encrypted into a
     * registration_token the client holds onto; the account is only ever
     * persisted once verifyRegistration() confirms the emailed code.
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::REGISTRATION_CODE_TTL_MINUTES);

        $registrationToken = Crypt::encryptString(json_encode([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'code' => $code,
            'expires_at' => $expiresAt->toIso8601String(),
        ]));

        try {
            Notification::route('mail', $validated['email'])
                ->notify(new RegistrationCode($code, self::REGISTRATION_CODE_TTL_MINUTES));
        } catch (\Throwable $e) {
            return $this->error(
                'We could not send the verification email right now. Please try again in a moment.',
                503,
                $e,
            );
        }

        return $this->success(
            'We sent a verification code to your email. Enter it to finish creating your account.',
            [
                'registration_token' => $registrationToken,
                'expires_in' => self::REGISTRATION_CODE_TTL_MINUTES * 60,
            ],
        );
    }

    /**
     * Decrypts the registration_token from register(), checks the code and
     * expiry, and only now creates the User row - this is the single point
     * where a pending signup becomes a real account.
     */
    public function verifyRegistration(Request $request)
    {
        $validated = $request->validate([
            'registration_token' => ['required', 'string'],
            'code' => ['required', 'string', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $invalid = fn () => ValidationException::withMessages([
            'code' => ['This code has expired or is invalid. Please register again.'],
        ]);

        try {
            $payload = json_decode(Crypt::decryptString($validated['registration_token']), true);
        } catch (\Throwable) {
            throw $invalid();
        }

        if (! is_array($payload) || ! isset($payload['email'], $payload['code'], $payload['expires_at'], $payload['password_hash'], $payload['name'])) {
            throw $invalid();
        }

        if (now()->greaterThan(Carbon::parse($payload['expires_at']))) {
            throw $invalid();
        }

        if (! hash_equals($payload['code'], $validated['code'])) {
            throw $invalid();
        }

        if (User::where('email', $payload['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered. Please sign in instead.'],
            ]);
        }

        $user = User::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password_hash'],
            'registration_source' => 'frontend',
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        // email_verified_at is deliberately not mass-assignable; this is the
        // one place allowed to set it, since the code above it already proved
        // ownership of the inbox.
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignRole('Normal');
        app(SubscriptionService::class)->ensureFreeSubscription($user);

        event(new Registered($user));

        return $this->authenticatedResponse($user, $validated['device_name'] ?? 'api-client', 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        if (! $user->canLogin()) {
            $message = match ($user->status) {
                User::STATUS_PENDING => 'Your account is pending approval. Please wait for an administrator to approve your account.',
                User::STATUS_REJECTED => 'Your account has been rejected. Please contact an administrator for more information.',
                User::STATUS_INACTIVE => 'Your account has been deactivated. Please contact an administrator.',
                default => 'Your account cannot be accessed at this time.',
            };

            return response()->json(['message' => $message], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address before signing in. Check your inbox for the verification link.',
                'requires_verification' => true,
            ], 403);
        }

        return $this->authenticatedResponse($user, $validated['device_name'] ?? 'api-client');
    }

    /** Send a public-site reset link without revealing whether an email exists. */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => $validated['email']]);

        return $this->success('If an account exists for this email address, a password reset link has been sent.');
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['This password reset link is invalid or has expired.'],
            ]);
        }

        return $this->success('Your password has been reset. You can now sign in.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function profile(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return $this->success($this->actorMessage('updated', 'profile'), [
            'user' => $this->userPayload($user),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => ['The current password is incorrect.']]);
        }

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return $this->success($this->actorMessage('updated', 'password'));
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar && Storage::disk('s3')->exists($user->avatar)) {
            Storage::disk('s3')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 's3');

        $user->update(['avatar' => $path]);

        return $this->success($this->actorMessage('updated', 'avatar'), [
            'avatar_url' => $user->avatar_url,
            'user' => $this->userPayload($user),
        ]);
    }

    private function authenticatedResponse(User $user, string $deviceName, int $status = 200)
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success('Authenticated successfully.', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], $status);
    }

    private function userPayload(User $user): array
    {
        $subscriptions = $user->activeSubscriptions()->with('plan')->get();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_verified' => $user->hasVerifiedEmail(),
            'is_google' => $user->registration_source === 'google',
            'avatar_url' => $user->avatar_url,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'subscription' => $subscriptions->map(fn ($sub) => [
                'id' => $sub->id,
                'status' => $sub->status,
                'starts_at' => $sub->starts_at?->format('Y-m-d'),
                'ends_at' => $sub->ends_at?->format('Y-m-d'),
                'plan' => $sub->plan ? [
                    'id' => $sub->plan->id,
                    'name' => $sub->plan->name,
                    'slug' => $sub->plan->slug,
                    'price' => $sub->plan->price,
                    'currency' => $sub->plan->currency,
                    'max_categories' => $sub->plan->max_categories,
                    'max_documents' => $sub->plan->max_documents,
                    'max_text_contents' => $sub->plan->max_text_contents,
                    'max_storage_mb' => $sub->plan->max_storage_mb,
                ] : null,
            ]),
        ];
    }
}
