<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasAnyPermission(['users.view', 'users.create', 'users.edit', 'users.delete']);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Users can update their own profile (name, email, password)
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->hasRole('Admin')) {
            // Prevent admins from deleting themselves
            return $user->id !== $model->id;
        }

        return $user->hasPermissionTo('users.delete');
    }

    public function assignCategories(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasPermissionTo('users.edit');
    }

    public function assignPlan(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasPermissionTo('users.edit');
    }
}
