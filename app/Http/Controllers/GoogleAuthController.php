<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $source = $request->query('source', 'frontend');
        $frontendUrl = $request->query('frontend_url', config('services.frontend.url', 'http://localhost:3000'));
        $redirectUri = $this->resolveRedirectUri($request);

        $stateData = [
            'source' => $source,
            'redirect_uri' => $redirectUri,
            'frontend_url' => $frontendUrl,
        ];

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUri)
            ->with([
                'state' => base64_encode(json_encode($stateData)),
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    public function callback(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $stateData = [];
        if ($request->filled('state')) {
            try {
                $decoded = json_decode(base64_decode($request->query('state')), true);
                if (is_array($decoded)) {
                    $stateData = $decoded;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to decode Google OAuth state', ['state' => $request->query('state')]);
            }
        }

        $source = $stateData['source'] ?? $request->query('source', 'frontend');
        $redirectUri = $stateData['redirect_uri'] ?? $this->resolveRedirectUri($request);
        $frontendUrl = $stateData['frontend_url'] ?? config('services.frontend.url', 'http://localhost:3000');

        // Check if user cancelled or Google returned an error
        if ($request->has('error')) {
            Log::info('Google OAuth cancelled or denied', ['error' => $request->query('error')]);
            if ($source === 'backend') {
                return redirect()->route('login')->withErrors(['email' => 'Google sign-in was cancelled or access was denied.']);
            }

            return redirect()->away(rtrim((string) $frontendUrl, '/').'/login?google_error=1');
        }

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($redirectUri)
                ->user();

            $email = $googleUser->getEmail();
            if (empty($email)) {
                throw new \RuntimeException('No email address provided by Google account.');
            }

            $user = User::firstOrCreate(['email' => $email], [
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google user',
                'password' => Hash::make(Str::random(48)),
                'status' => User::STATUS_APPROVED,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'registration_source' => 'google',
            ]);

            // Ensure account is verified and approved
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }
            if ($user->status === User::STATUS_PENDING && ! $user->approved_at) {
                $user->status = User::STATUS_APPROVED;
                $user->approved_at = now();
            }
            $user->save();

            if (Role::where('name', 'Normal')->exists() && ! $user->hasRole('Normal')) {
                $user->assignRole('Normal');
            }
            $subscriptions->ensureFreeSubscription($user);

            if ($source === 'backend') {
                Auth::login($user, true);
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }

            $token = $user->createToken('google-oauth')->plainTextToken;

            return redirect()->away(rtrim((string) $frontendUrl, '/').'/auth/google/callback?'.http_build_query(['token' => $token]));
        } catch (\Throwable $e) {
            Log::error('Google Auth Callback Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if ($source === 'backend') {
                return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
            }

            return redirect()->away(rtrim((string) $frontendUrl, '/').'/login?google_error=1');
        }
    }

    protected function resolveRedirectUri(Request $request): string
    {
        $configured = config('services.google.redirect');

        if (! empty($configured)) {
            return $configured;
        }

        return url('/auth/google/callback');
    }
}
