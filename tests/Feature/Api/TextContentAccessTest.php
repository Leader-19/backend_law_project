<?php

use App\Models\Category;
use App\Models\TextContent;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->public = Category::create(['title' => 'Public Laws', 'access_type' => 'public', 'user_id' => $this->owner->id]);
    $this->planOnly = Category::create(['title' => 'Plan-only Laws', 'access_type' => 'plan', 'user_id' => $this->owner->id]);

    $this->publicContent = TextContent::create([
        'title' => 'Public Article', 'body' => 'Visible to everyone.',
        'category_id' => $this->public->id, 'user_id' => $this->owner->id,
    ]);
    $this->restrictedContent = TextContent::create([
        'title' => 'Restricted Article', 'body' => 'Plan members only.',
        'category_id' => $this->planOnly->id, 'user_id' => $this->owner->id,
    ]);
});

test('a guest only sees text content from public categories on the index endpoint', function () {
    $response = $this->getJson('/api/text-contents');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Public Article');
    expect($titles)->not->toContain('Restricted Article');
});

test('a guest can view a public text content directly but not a plan-only one', function () {
    $this->getJson("/api/text-contents/{$this->publicContent->id}")->assertOk();
    $this->getJson("/api/text-contents/{$this->restrictedContent->id}")->assertForbidden();
});

test('a random authenticated user without category access cannot read plan-only text content', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->getJson("/api/text-contents/{$this->restrictedContent->id}")
        ->assertForbidden();

    $response = $this->actingAs($stranger)->getJson('/api/text-contents');
    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->not->toContain('Restricted Article');
});

test('a random authenticated user cannot update text content they have no permission over', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->putJson("/api/text-contents/{$this->publicContent->id}", ['title' => 'Hijacked', 'category_id' => $this->public->id])
        ->assertForbidden();

    expect($this->publicContent->fresh()->title)->toBe('Public Article');
});

test('a random authenticated user cannot delete text content they have no permission over', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->deleteJson("/api/text-contents/{$this->publicContent->id}")
        ->assertForbidden();

    expect(TextContent::find($this->publicContent->id))->not->toBeNull();
});

test('a random authenticated user cannot create text content in a category they have no permission over', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->postJson('/api/text-contents', [
            'title' => 'Sneaky',
            'body' => 'Should not be allowed.',
            'category_id' => $this->public->id,
        ])
        ->assertForbidden();

    expect(TextContent::where('title', 'Sneaky')->exists())->toBeFalse();
});
