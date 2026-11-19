<?php

use App\Models\Category;
use App\Models\Document;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

test('a guest sees public categories on the categories endpoint', function () {
    Category::create(['title' => 'Public Laws', 'access_type' => 'public', 'user_id' => $this->owner->id]);
    Category::create(['title' => 'Plan-only Laws', 'access_type' => 'plan', 'user_id' => $this->owner->id]);

    $response = $this->getJson('/api/categories');

    $response->assertOk();
    $titles = collect($response->json('categories'))->pluck('title');
    expect($titles)->toContain('Public Laws');
    expect($titles)->not->toContain('Plan-only Laws');
});

test('a guest sees documents from public categories on the preview endpoint', function () {
    $public = Category::create(['title' => 'Public Laws', 'access_type' => 'public', 'user_id' => $this->owner->id]);
    $planOnly = Category::create(['title' => 'Plan-only Laws', 'access_type' => 'plan', 'user_id' => $this->owner->id]);
    Document::create(['doc_name' => 'public.pdf', 'doc_title' => 'Public Doc', 'doc_upload' => 'documents/public.pdf', 'category_id' => $public->id, 'user_id' => $this->owner->id]);
    Document::create(['doc_name' => 'plan.pdf', 'doc_title' => 'Plan Doc', 'doc_upload' => 'documents/plan.pdf', 'category_id' => $planOnly->id, 'user_id' => $this->owner->id]);

    $response = $this->getJson('/api/documents/preview?limit=10');

    $response->assertOk();
    $titles = collect($response->json('categories'))->pluck('title');
    expect($titles)->toContain('Public Laws');
    expect($titles)->not->toContain('Plan-only Laws');
});

test('a guest sees documents from public categories on the index endpoint', function () {
    $public = Category::create(['title' => 'Public Laws', 'access_type' => 'public', 'user_id' => $this->owner->id]);
    $planOnly = Category::create(['title' => 'Plan-only Laws', 'access_type' => 'plan', 'user_id' => $this->owner->id]);
    Document::create(['doc_name' => 'public.pdf', 'doc_title' => 'Public Doc', 'doc_upload' => 'documents/public.pdf', 'category_id' => $public->id, 'user_id' => $this->owner->id]);
    Document::create(['doc_name' => 'plan.pdf', 'doc_title' => 'Plan Doc', 'doc_upload' => 'documents/plan.pdf', 'category_id' => $planOnly->id, 'user_id' => $this->owner->id]);

    $response = $this->getJson('/api/documents');

    $response->assertOk();
    $titles = collect($response->json('categories'))->pluck('title');
    expect($titles)->toContain('Public Laws');
    expect($titles)->not->toContain('Plan-only Laws');
});

test('a guest with no public categories at all gets an empty, not broken, response', function () {
    Category::create(['title' => 'Plan-only Laws', 'access_type' => 'plan', 'user_id' => $this->owner->id]);

    $this->getJson('/api/categories')->assertOk()->assertJson(['categories' => []]);
    $this->getJson('/api/documents/preview')->assertOk();
    $this->getJson('/api/documents')->assertOk()->assertJson(['categories' => []]);
});
