<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class CategoryUserController extends Controller
{
    public function index(Request $request, Category $category)
    {
        $this->authorize('manage', $category);
        $perPage = min(max((int) $request->integer('per_page', 10), 5), 100);
        $search = trim((string) $request->query('search', ''));

        $users = $category->users()
            ->withPivot('permission')
            ->orderBy('name')
            ->paginate($perPage, ['users.id', 'users.name', 'users.email'], 'assigned_page')
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permission' => $user->pivot->permission,
                ];
            });

        $allUsers = User::query()
            ->where('id', '!=', $request->user()->id)
            ->when($search !== '', fn ($query) => $query->where(fn ($users) => $users
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'email'], 'user_page')
            ->withQueryString();

        $teams = $category->teams()->orderBy('name')->get(['roles.id', 'roles.name'])
            ->map(fn ($role) => ['id' => $role->id, 'name' => $role->name, 'permission' => $role->pivot->permission]);

        return Inertia::render('Category/CategoryPermissions', [
            'category' => $category,
            'users' => $users,
            'allUsers' => $allUsers,
            'teams' => $teams,
            'availableTeams' => Role::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'per_page' => $perPage],
            'permissions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ]);
    }

    public function store(Request $request, Category $category)
    {
        $this->authorize('manage', $category);

        if ($request->has('assignments')) {
            $validated = $request->validate([
                'assignments' => ['required', 'array'],
                'assignments.*.user_id' => ['required', 'integer', 'exists:users,id'],
                'assignments.*.permissions' => ['required', 'array'],
                'assignments.*.permissions.*' => ['required', 'string', 'in:view,create,edit,delete,manage'],
            ]);

            foreach ($validated['assignments'] as $assignment) {
                $permission = implode(',', $assignment['permissions']);
                $category->users()->syncWithoutDetaching([$assignment['user_id'] => ['permission' => $permission]]);
            }

            return back()->with('success', 'Permissions assigned successfully!');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['required', 'string', 'in:view,create,edit,delete,manage'],
        ]);

        $category->users()->detach($validated['user_id']);
        $category->users()->attach($validated['user_id'], [
            'permission' => $validated['permission'],
        ]);

        return back()->with('success', 'Permission assigned successfully!');
    }

    public function update(Request $request, Category $category, User $user)
    {
        $this->authorize('manage', $category);
        $validated = $request->validate([
            'permission' => ['required', 'string', 'in:view,create,edit,delete,manage'],
        ]);

        $category->users()->updateExistingPivot($user->id, [
            'permission' => $validated['permission'],
        ]);

        return back()->with('success', 'Permission updated successfully!');
    }

    public function destroy(Request $request, Category $category, User $user)
    {
        $this->authorize('manage', $category);
        $category->users()->detach($user->id);

        return back()->with('success', 'Permission removed successfully!');
    }

    public function storeTeam(Request $request, Category $category)
    {
        $this->authorize('manage', $category);
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permission' => ['required', 'string', 'in:view,create,edit,delete,manage'],
        ]);

        $category->teams()->syncWithoutDetaching([$validated['role_id'] => ['permission' => $validated['permission']]]);

        return back()->with('success', 'Team permission assigned successfully!');
    }

    public function updateTeam(Request $request, Category $category, Role $role)
    {
        $this->authorize('manage', $category);
        $validated = $request->validate(['permission' => ['required', 'string', 'in:view,create,edit,delete,manage']]);
        $category->teams()->updateExistingPivot($role->id, ['permission' => $validated['permission']]);

        return back()->with('success', 'Team permission updated successfully!');
    }

    public function destroyTeam(Category $category, Role $role)
    {
        $this->authorize('manage', $category);
        $category->teams()->detach($role->id);

        return back()->with('success', 'Team permission removed successfully!');
    }
}
