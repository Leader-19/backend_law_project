<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoutePermissionService
{
    /** Create permissions from named resource routes and give every permission to Admin. */
    public function sync(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $actions = [
            'index' => 'view', 'show' => 'view',
            'create' => 'create', 'store' => 'create', 'scan' => 'create',
            'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete',
        ];
        $aliases = ['documents' => 'document', 'categories' => 'category'];
        $created = 0;

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_contains($name, '.')) {
                continue;
            }

            [$resource, $action] = explode('.', $name, 2);
            if (! isset($actions[$action])) {
                continue;
            }

            $permission = ($aliases[$resource] ?? $resource) . '.' . $actions[$action];
            $model = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $created += $model->wasRecentlyCreated ? 1 : 0;
        }

        Role::firstOrCreate(['name' => 'Admin'])->syncPermissions(Permission::all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }
}
