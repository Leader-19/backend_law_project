<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'action',
        'severity',
        'description',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter system issues (non-CRUD actions).
     */
    public function scopeSystemIssues($query)
    {
        return $query->where('action', 'issue');
    }

    /** Record an auditable action and attach the current authenticated user. */
    public static function record(string $action, string $description, ?Model $subject = null, ?array $newData = null): self
    {
        $actor = auth()->user();
        $request = app()->bound('request') ? request() : null;

        return static::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'causer_type' => $actor ? $actor->getMorphClass() : null,
            'causer_id' => $actor?->getKey(),
            'action' => $action,
            'description' => $description,
            'new_data' => $newData,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
