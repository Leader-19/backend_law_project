<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin') || $user->categoryPermissions()->exists();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('view', $category);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasAnyPermission(['category.create', 'category.manage']);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('edit', $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('delete', $category);
    }

    public function manage(User $user, Category $category): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('manage', $category);
    }
}
