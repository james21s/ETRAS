<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'committee_id', 'organization',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    /** Papers this user authored. */
    public function papers(): HasMany
    {
        return $this->hasMany(Paper::class);
    }

    /** Review assignments (as a reviewer). */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /** Many-to-many through the reviews table: papers this user reviews. */
    public function reviewingPapers(): BelongsToMany
    {
        return $this->belongsToMany(Paper::class, 'reviews', 'reviewer_id', 'paper_id')
            ->withPivot(['status', 'recommendation']);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isExecutive(): bool
    {
        return $this->hasRole(UserRole::Executive);
    }

    public function isEditor(): bool
    {
        return $this->hasRole(UserRole::Editor);
    }

    public function isReviewer(): bool
    {
        return $this->hasRole(UserRole::Reviewer);
    }

    public function isAuthor(): bool
    {
        return $this->hasRole(UserRole::Author);
    }

    public function isSubscriber(): bool
    {
        return $this->hasRole(UserRole::Subscriber);
    }
}
