<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;

trait LogsActivity
{
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->writeActivityLog('created', null, $model->activityLogAttributes($model->getAttributes()));
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

            $model->writeActivityLog(
                'updated',
                $model->activityLogAttributes($old),
                $model->activityLogAttributes($dirty),
            );
        });

        static::deleted(function ($model) {
            $model->writeActivityLog('deleted', $model->activityLogAttributes($model->getAttributes()), null);
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

    /** Never store credentials or authentication secrets in an audit record. */
    protected function activityLogAttributes(array $attributes): array
    {
        return Arr::except($attributes, [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
    }
}
