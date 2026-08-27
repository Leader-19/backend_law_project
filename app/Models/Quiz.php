<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'created_by',
        'passing_score',
        'time_limit_minutes',
        'max_attempts',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'passing_score' => 'integer',
            'time_limit_minutes' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id')->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function attemptsForUser(User $user): HasMany
    {
        return $this->attempts()->where('user_id', $user->id);
    }

    public function canUserAttempt(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_attempts > 0) {
            $attemptCount = $this->attemptsForUser($user)->count();
            if ($attemptCount >= $this->max_attempts) {
                return false;
            }
        }

        return true;
    }

    public function totalQuestions(): int
    {
        return $this->questions()->count();
    }
}
