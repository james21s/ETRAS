<?php

namespace App\Models;

use App\Enums\PaperStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'paper_status_histories';

    protected $fillable = ['paper_id', 'user_id', 'from_status', 'to_status', 'note'];

    protected function casts(): array
    {
        return [
            'from_status' => PaperStatus::class,
            'to_status' => PaperStatus::class,
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
