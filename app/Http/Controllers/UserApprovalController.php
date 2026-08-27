<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'pending');

        $query = User::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $users = $query->with('roles')
            ->latest()
            ->paginate(15);

        return Inertia::render('Users/UserApproval', [
            'users' => $users,
            'currentStatus' => $status,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->status === User::STATUS_PENDING, 422, 'Only pending users can be approved.');

        $user->update([
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "User {$user->name} has been approved.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->status === User::STATUS_PENDING, 422, 'Only pending users can be rejected.');

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $user->update([
            'status' => User::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return back()->with('success', "User {$user->name} has been rejected.");
    }

    public function deactivate(User $user): RedirectResponse
    {
        abort_unless($user->status === User::STATUS_APPROVED, 422, 'Only approved users can be deactivated.');

        $user->update(['status' => User::STATUS_INACTIVE]);

        return back()->with('success', "User {$user->name} has been deactivated.");
    }

    public function activate(User $user): RedirectResponse
    {
        abort_unless($user->status === User::STATUS_INACTIVE, 422, 'Only inactive users can be activated.');

        $user->update([
            'status' => User::STATUS_APPROVED,
            'approved_at' => $user->approved_at ?? now(),
        ]);

        return back()->with('success', "User {$user->name} has been activated.");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        User::whereIn('id', $request->input('ids'))
            ->where('status', User::STATUS_PENDING)
            ->update([
                'status' => User::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

        return back()->with('success', 'Selected users have been approved.');
    }

    public function bulkReject(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        User::whereIn('id', $request->input('ids'))
            ->where('status', User::STATUS_PENDING)
            ->update(['status' => User::STATUS_REJECTED]);

        return back()->with('success', 'Selected users have been rejected.');
    }
}
