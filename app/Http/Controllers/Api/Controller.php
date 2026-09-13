<?php

namespace App\Http\Controllers\Api;

use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

abstract class Controller extends \App\Http\Controllers\Controller
{
    use LogsActivity;

    protected function actorName(Request $request): string
    {
        $user = $request->user();

        return $user?->name ?? 'System';
    }

    protected function actorMessage(string $action, string $subject): string
    {
        $actor = $this->actorName(request());

        return "{$actor} {$action} the {$subject}.";
    }

    protected function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            ...$data,
        ], $status);
    }

    protected function error(string $message, int $status = 400, ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            Log::channel('stack')->error($message, [
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }

    protected function validationError(ValidationException $e): JsonResponse
    {
        Log::channel('stack')->warning('Validation failed', [
            'errors' => $e->errors(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], 422);
    }
}
