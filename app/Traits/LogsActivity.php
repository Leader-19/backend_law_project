<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait LogsActivity
{
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->writeActivityLog('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();

            if ($dirty === []) {
                return;
            }

            $old = [];

            foreach (array_keys($dirty) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            $model->writeActivityLog('updated', $old, $dirty);
        });

        static::deleted(function ($model) {
            $model->writeActivityLog('deleted', $model->getAttributes(), null);
        });
    }

    protected function writeActivityLog(string $action, ?array $oldData, ?array $newData): void
    {
        ActivityLog::create([
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'causer_type' => filled(auth()->user()) ? get_class(auth()->user()) : null,
            'causer_id' => auth()->id(),
            'action' => $action,
            'description' => class_basename(static::class).' '.$action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
