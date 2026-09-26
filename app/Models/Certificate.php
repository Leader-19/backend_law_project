<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_id',
        'attempt_id',
        'certificate_number',
        'score',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public static function generateCertificateNumber(): string
    {
        return 'CERT-'.strtoupper(Str::random(4)).'-'.date('Ymd').'-'.strtoupper(Str::random(4));
    }
}
