<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'registration_source' => 'frontend',
            'status' => User::STATUS_PENDING,
        ]);

        $user->assignRole('Normal');

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

        return $this->authenticatedResponse($user, $validated['device_name'] ?? 'api-client');
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

        return response()->json(['message' => 'Profile updated successfully.', 'user' => $this->userPayload($user)]);
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

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        $avatarUrl = asset(Storage::url($path));

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'avatar_url' => $avatarUrl,
            'user' => $this->userPayload($user),
        ]);
    }

    private function authenticatedResponse(User $user, string $deviceName, int $status = 200)
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
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
            'avatar_url' => $user->avatar ? asset(Storage::url($user->avatar)) : null,
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
