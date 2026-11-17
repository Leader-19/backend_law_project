<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Handle the signed verification link from the email. Deliberately not
     * guarded by "auth" - the browser tab that opens this link has no
     * Bearer token to send, so this validates the signature + hash itself
     * instead of relying on an authenticated session.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $user = User::find($id);

        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()->away("{$frontendUrl}/login?verified=0");
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->away("{$frontendUrl}/login?verified=1");
    }

    /**
     * Resend the verification email. Mirrors forgotPassword's anti-enumeration
     * style: same response whether or not the account exists.
     */
    public function resend(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $validated['email'])->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return $this->success('If an account exists for this email and is not yet verified, a new verification link has been sent.');
    }
}
