<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Sector $sector) {
            $sector->slug ??= Str::slug($sector->name);
        });
    }

    /** Many-to-Many with papers through the paper_sector pivot. */
    public function papers(): BelongsToMany
    {
        return $this->belongsToMany(Paper::class)->withPivot('is_primary')->withTimestamps();
    }
}
