<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $role = Role::firstOrCreate(['name' => 'Normal', 'guard_name' => 'web']);
    $permission = Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole('Normal');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});
