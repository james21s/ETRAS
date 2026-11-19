<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const COMPLETED = 'completed';

    public const RECOMMENDATIONS = [
        'accept' => 'Accept as is',
        'minor_revision' => 'Accept with minor revision',
        'major_revision' => 'Major revision required',
        'reject' => 'Reject',
    ];

    protected $fillable = [
        'paper_id', 'reviewer_id', 'status', 'recommendation', 'score',
        'comments_to_author', 'confidential_notes', 'assigned_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('reviews.status', self::PENDING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('reviews.status', self::COMPLETED);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }
}
