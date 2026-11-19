<?php

use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->restricted = Category::create(['title' => 'Plan-only Laws', 'access_type' => 'plan', 'user_id' => $this->owner->id]);
    $this->quiz = Quiz::create([
        'title' => 'Restricted Quiz', 'category_id' => $this->restricted->id, 'created_by' => $this->owner->id,
        'passing_score' => 50, 'max_attempts' => 0, 'is_active' => true,
    ]);
    $this->question = QuizQuestion::create(['quiz_id' => $this->quiz->id, 'question' => 'What is 2+2?', 'type' => 'single', 'sort_order' => 1]);
    $this->correctOption = QuizOption::create(['question_id' => $this->question->id, 'option_text' => '4', 'is_correct' => true, 'sort_order' => 1]);
    $this->wrongOption = QuizOption::create(['question_id' => $this->question->id, 'option_text' => '5', 'is_correct' => false, 'sort_order' => 2]);

    $this->stranger = User::factory()->create();

    Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('a user without access to the category cannot view a quiz', function () {
    $this->actingAs($this->stranger)
        ->getJson("/api/quizzes/{$this->quiz->id}")
        ->assertForbidden();
});

test('a user without access to the category cannot take a quiz', function () {
    $this->actingAs($this->stranger)
        ->getJson("/api/quizzes/{$this->quiz->id}/take")
        ->assertForbidden();
});

test('a user without access to the category cannot submit a quiz', function () {
    $this->actingAs($this->stranger)
        ->postJson("/api/quizzes/{$this->quiz->id}/submit", [
            'answers' => [['question_id' => $this->question->id, 'option_id' => $this->correctOption->id]],
        ])
        ->assertForbidden();
});

test('a quiz response never reveals which option is correct before submission', function () {
    $response = $this->actingAs($this->admin)->getJson("/api/quizzes/{$this->quiz->id}/take");

    $response->assertOk();
    $options = $response->json('quiz.questions.0.options');
    expect(collect($options)->first())->not->toHaveKey('is_correct');
});

test('a user with category access can still take and submit the quiz normally', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/quizzes/{$this->quiz->id}/take")
        ->assertOk();

    $this->actingAs($this->admin)
        ->postJson("/api/quizzes/{$this->quiz->id}/submit", [
            'answers' => [['question_id' => $this->question->id, 'option_id' => $this->correctOption->id]],
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Congratulations! You passed the quiz!');
});
