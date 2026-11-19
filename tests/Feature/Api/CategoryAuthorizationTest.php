<?php

use App\Models\Category;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->categoryA = Category::create(['title' => 'Category A', 'access_type' => 'plan', 'user_id' => $this->owner->id]);
    $this->categoryB = Category::create(['title' => 'Category B', 'access_type' => 'plan', 'user_id' => $this->owner->id]);

    $editPermission = Permission::create(['name' => 'category.edit', 'guard_name' => 'web']);
    $deletePermission = Permission::create(['name' => 'category.delete', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Category Editor', 'guard_name' => 'web']);
    $role->givePermissionTo([$editPermission, $deletePermission]);

    $this->editor = User::factory()->create();
    $this->editor->assignRole($role);
    // Scoped permission on category A only, nothing on B.
    $this->editor->categoryPermissions()->attach($this->categoryA->id, ['permission' => 'edit,delete']);
});

test('a user with the global edit permission cannot update a category they have no scoped access to', function () {
    $this->actingAs($this->editor)
        ->putJson("/api/categories/{$this->categoryB->id}", ['title' => 'Hijacked'])
        ->assertForbidden();

    expect($this->categoryB->fresh()->title)->toBe('Category B');
});

test('a user with the global delete permission cannot delete a category they have no scoped access to', function () {
    $this->actingAs($this->editor)
        ->deleteJson("/api/categories/{$this->categoryB->id}")
        ->assertForbidden();

    expect(Category::find($this->categoryB->id))->not->toBeNull();
});

test('a user can still update and delete a category they do have scoped access to', function () {
    $this->actingAs($this->editor)
        ->putJson("/api/categories/{$this->categoryA->id}", ['title' => 'Updated A'])
        ->assertOk();

    expect($this->categoryA->fresh()->title)->toBe('Updated A');

    $this->actingAs($this->editor)
        ->deleteJson("/api/categories/{$this->categoryA->id}")
        ->assertNoContent();

    expect(Category::find($this->categoryA->id))->toBeNull();
});
