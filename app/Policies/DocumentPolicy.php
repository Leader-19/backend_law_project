<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('view', $document->category);
    }

    public function create(User $user, Category $category): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('edit', $category);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('edit', $document->category);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('Admin') || $user->hasCategoryPermission('delete', $document->category);
    }
}
