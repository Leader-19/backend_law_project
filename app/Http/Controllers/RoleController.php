<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 5), 100);
        $search = trim((string) $request->query('search', ''));

        $query = Role::with('permissions');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('permissions', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $roles = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => $roles->getCollection()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => array_values($role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all()),
            ])->all(),
            'pagination' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Roles/Create', [
            'permissions' => Permission::pluck('name')->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return to_route('roles.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::find($id);

        return Inertia::render('Roles/Show', [
            'role' => $role,
            'rolePermissions' => $role->permissions()->pluck('name')->all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::find($id);

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'rolePermissions' => $role->permissions()->pluck('name')->all(),
            'permissions' => Permission::pluck('name')->all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$id],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::findOrFail($id);

        $role->name = $request->name;
        $role->save();
        $role->syncPermissions($request->permissions);

        return to_route('roles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete', [Role::class, $id]);
        Role::destroy($id);

        return to_route('roles.index');
    }
}
