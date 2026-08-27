<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    protected $table = 'reading_history';

    protected $fillable = [
        'user_id',
        'document_id',
        'last_page',
        'total_pages',
        'progress_percent',
        'last_opened_at',
    ];

    protected function casts(): array
    {
        return [
            'last_opened_at' => 'datetime',
            'progress_percent' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
