<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\TextContent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TextContentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TextContent $textContent): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('view', $textContent->category);
    }

    public function create(User $user, Category $category): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('edit', $category);
    }

    public function update(User $user, TextContent $textContent): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('edit', $textContent->category);
    }

    public function delete(User $user, TextContent $textContent): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('delete', $textContent->category);
    }
}
