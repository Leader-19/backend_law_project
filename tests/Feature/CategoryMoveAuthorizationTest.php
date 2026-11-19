<?php

use App\Models\Category;
use App\Models\Document;
use App\Models\TextContent;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->categoryA = Category::create(['title' => 'Category A', 'access_type' => 'plan', 'user_id' => $this->owner->id]);
    $this->categoryB = Category::create(['title' => 'Category B', 'access_type' => 'plan', 'user_id' => $this->owner->id]);

    $editDoc = Permission::create(['name' => 'document.edit', 'guard_name' => 'web']);
    $editText = Permission::create(['name' => 'text_content.edit', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $role->givePermissionTo([$editDoc, $editText]);

    $this->editor = User::factory()->create();
    $this->editor->assignRole($role);
    // Scoped edit permission on category A only, nothing on B.
    $this->editor->categoryPermissions()->attach($this->categoryA->id, ['permission' => 'edit']);
});

test('a user cannot move a document into a category they have no edit permission on', function () {
    $document = Document::create([
        'doc_name' => 'law.pdf', 'doc_title' => 'A Law', 'doc_upload' => 'documents/law.pdf',
        'category_id' => $this->categoryA->id, 'user_id' => $this->owner->id,
    ]);

    $this->actingAs($this->editor)
        ->put(route('documents.update', $document), [
            'doc_name' => 'law.pdf', 'doc_title' => 'Renamed',
            'category_id' => $this->categoryB->id, 'description' => null,
        ])
        ->assertForbidden();

    expect($document->fresh()->category_id)->toBe($this->categoryA->id);
});

test('a user can still update a document without changing its category', function () {
    $document = Document::create([
        'doc_name' => 'law.pdf', 'doc_title' => 'A Law', 'doc_upload' => 'documents/law.pdf',
        'category_id' => $this->categoryA->id, 'user_id' => $this->owner->id,
    ]);

    $this->actingAs($this->editor)
        ->put(route('documents.update', $document), [
            'doc_name' => 'law.pdf', 'doc_title' => 'Renamed',
            'category_id' => $this->categoryA->id, 'description' => null,
        ])
        ->assertRedirect();

    expect($document->fresh()->doc_title)->toBe('Renamed');
});

test('a user cannot move a text content into a category they have no edit permission on', function () {
    $textContent = TextContent::create([
        'title' => 'Article', 'body' => 'Body text.',
        'category_id' => $this->categoryA->id, 'user_id' => $this->owner->id,
    ]);

    $this->actingAs($this->editor)
        ->put(route('text-contents.update', $textContent), [
            'title' => 'Hijacked', 'body' => 'Body text.', 'category_id' => $this->categoryB->id,
        ])
        ->assertForbidden();

    expect($textContent->fresh()->category_id)->toBe($this->categoryA->id);
});

test('api cannot move a text content into a category they have no edit permission on', function () {
    $textContent = TextContent::create([
        'title' => 'Article', 'body' => 'Body text.',
        'category_id' => $this->categoryA->id, 'user_id' => $this->owner->id,
    ]);

    $this->actingAs($this->editor)
        ->putJson("/api/text-contents/{$textContent->id}", [
            'title' => 'Hijacked', 'body' => 'Body text.', 'category_id' => $this->categoryB->id,
        ])
        ->assertForbidden();

    expect($textContent->fresh()->category_id)->toBe($this->categoryA->id);
});
