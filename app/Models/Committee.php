<?php

namespace App\Models;

use App\Enums\PaperStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Committee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Committee $committee) {
            $committee->slug ??= Str::slug($committee->name);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Has-Many-Through: Committee -> User -> Paper.
     * Every paper written by a member of the committee ("institutional publishing footprint").
     */
    public function papers(): HasManyThrough
    {
        return $this->hasManyThrough(Paper::class, User::class, 'committee_id', 'user_id');
    }

    public function publishedPapers(): HasManyThrough
    {
        return $this->papers()->where('papers.status', PaperStatus::Published->value);
    }
}
