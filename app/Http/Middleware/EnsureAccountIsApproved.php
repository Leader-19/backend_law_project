<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canLogin()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = match ($user->status) {
                User::STATUS_PENDING => 'Your account is pending approval. Please wait for an administrator to approve your account.',
                User::STATUS_REJECTED => 'Your account has been rejected. Please contact an administrator for more information.',
                User::STATUS_INACTIVE => 'Your account has been deactivated. Please contact an administrator.',
                default => 'Your account cannot be accessed at this time.',
            };

            return redirect()->route('login')->withErrors([
                'email' => $message,
            ]);
        }

        return $next($request);
    }
}
