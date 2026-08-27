<?php

namespace App\Http\Controllers;

use App\Services\RoutePermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->integer('per_page', 20), 5), 100);

        $permissions = Permission::query()
            ->withCount('roles')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'guard_name', 'created_at'])
            ->withQueryString();

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => ['search' => $search],
        ]);
    }

    public function scan(RoutePermissionService $permissions)
    {
        $created = $permissions->sync();

        return to_route('permissions.index')
            ->with('success', "Permission scan completed. {$created} new permission(s) created.");
    }
}
