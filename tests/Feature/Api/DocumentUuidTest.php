<?php

use App\Models\Category;
use App\Models\Document;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->category = Category::create(['title' => 'Public Laws', 'access_type' => 'public', 'user_id' => $this->owner->id]);
    $this->document = Document::create([
        'doc_name' => 'law.pdf',
        'doc_title' => 'A Public Law',
        'doc_upload' => 'documents/law.pdf',
        'category_id' => $this->category->id,
        'user_id' => $this->owner->id,
    ]);
    $this->user = User::factory()->create();
});

test('a document is given a uuid automatically on creation', function () {
    expect($this->document->uuid)->not->toBeNull();
    expect($this->document->uuid)->toHaveLength(36);
});

test('two documents never share a uuid', function () {
    $other = Document::create([
        'doc_name' => 'other.pdf',
        'doc_title' => 'Another Law',
        'doc_upload' => 'documents/other.pdf',
        'category_id' => $this->category->id,
        'user_id' => $this->owner->id,
    ]);

    expect($this->document->uuid)->not->toBe($other->uuid);
});

test('a document can be fetched by uuid but not by its numeric id', function () {
    $this->actingAs($this->user)
        ->getJson("/api/documents/{$this->document->uuid}")
        ->assertOk()
        ->assertJsonPath('document.id', $this->document->uuid);

    $this->actingAs($this->user)
        ->getJson("/api/documents/{$this->document->id}")
        ->assertNotFound();
});

test('document listings expose the uuid as the id field, not the numeric id', function () {
    $response = $this->getJson('/api/documents');

    $response->assertOk();
    $doc = collect($response->json('categories'))->firstWhere('title', 'Public Laws')['documents'][0];
    expect($doc['id'])->toBe($this->document->uuid);
});

test('a user can add, check, and remove a document from their library by uuid', function () {
    $this->actingAs($this->user)
        ->postJson('/api/library', ['document_id' => $this->document->uuid])
        ->assertOk();

    $this->actingAs($this->user)
        ->postJson('/api/library/check', ['document_id' => $this->document->uuid])
        ->assertOk()
        ->assertJsonPath('in_library', true);

    $libraryResponse = $this->actingAs($this->user)->getJson('/api/library');
    $libraryResponse->assertOk();
    expect($libraryResponse->json('data.0.id'))->toBe($this->document->uuid);

    $this->actingAs($this->user)
        ->deleteJson("/api/library/{$this->document->uuid}")
        ->assertOk();

    $this->actingAs($this->user)
        ->postJson('/api/library/check', ['document_id' => $this->document->uuid])
        ->assertOk()
        ->assertJsonPath('in_library', false);
});

test('reading progress can be recorded and fetched by document uuid', function () {
    $this->actingAs($this->user)
        ->postJson('/api/reading-history/progress', [
            'document_id' => $this->document->uuid,
            'last_page' => 5,
            'total_pages' => 10,
        ])
        ->assertOk()
        ->assertJsonPath('progress_percent', '50.00');

    $this->actingAs($this->user)
        ->getJson("/api/reading-history/{$this->document->uuid}/progress")
        ->assertOk()
        ->assertJsonPath('last_page', 5);
});
