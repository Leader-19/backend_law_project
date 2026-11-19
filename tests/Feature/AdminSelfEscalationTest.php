<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    Role::create(['name' => 'Normal', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.edit', 'guard_name' => 'web']);

    $editorRole = Role::create(['name' => 'User Editor', 'guard_name' => 'web']);
    $editorRole->givePermissionTo(['users.create', 'users.edit']);

    $this->staff = User::factory()->create();
    $this->staff->assignRole($editorRole);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('a user with only users.edit cannot promote themselves to Admin', function () {
    $this->actingAs($this->staff)
        ->put(route('users.update', $this->staff), [
            'name' => $this->staff->name,
            'email' => $this->staff->email,
            'roles' => ['Admin'],
        ])
        ->assertForbidden();

    expect($this->staff->fresh()->hasRole('Admin'))->toBeFalse();
});

test('a user with only users.edit cannot promote someone else to Admin', function () {
    $victim = User::factory()->create();

    $this->actingAs($this->staff)
        ->put(route('users.update', $victim), [
            'name' => $victim->name,
            'email' => $victim->email,
            'roles' => ['Admin'],
        ])
        ->assertForbidden();

    expect($victim->fresh()->hasRole('Admin'))->toBeFalse();
});

test('a user with only users.create cannot create a new Admin account', function () {
    $this->actingAs($this->staff)
        ->post(route('users.store'), [
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'roles' => ['Admin'],
        ])
        ->assertForbidden();

    expect(User::where('email', 'sneaky@example.com')->exists())->toBeFalse();
});

test('a real Admin can still grant the Admin role normally', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => ['Admin'],
        ])
        ->assertRedirect();

    expect($target->fresh()->hasRole('Admin'))->toBeTrue();
});

test('a user with only users.edit can still assign non-admin roles normally', function () {
    $target = User::factory()->create();

    $this->actingAs($this->staff)
        ->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => ['Normal'],
        ])
        ->assertRedirect();

    expect($target->fresh()->hasRole('Normal'))->toBeTrue();
});

test('the frontend user editor has the same Admin-escalation guard', function () {
    $victim = User::factory()->create(['registration_source' => 'frontend']);

    $this->actingAs($this->staff)
        ->put(route('frontend-users.update', $victim->id), [
            'name' => $victim->name,
            'email' => $victim->email,
            'roles' => ['Admin'],
        ])
        ->assertForbidden();

    expect($victim->fresh()->hasRole('Admin'))->toBeFalse();
});
