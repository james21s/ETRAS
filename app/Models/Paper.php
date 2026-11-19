<?php

namespace App\Models;

use App\Enums\PaperStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Paper extends Model
{
    use HasFactory;

    /** Shared placeholder PDF used by seeded records; never deleted by the app. */
    public const SEED_DIRECTORY = 'papers/seed/';
    public const SEED_SAMPLE_PATH = 'papers/seed/sample.pdf';

    protected $fillable = [
        'user_id', 'title', 'slug', 'abstract', 'status', 'version', 'reference',
        'file_path', 'file_original_name', 'file_size', 'file_mime',
        'submitted_at', 'approved_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaperStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Paper $paper) {
            $paper->slug ??= Str::slug(Str::limit($paper->title, 60, '')).'-'.Str::lower(Str::random(6));
        });
    }

    /* ----------------------------- Relationships ----------------------------- */

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Many-to-Many: a paper is indexed under several technical sectors. */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class)->withPivot('is_primary')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Many-to-Many through the reviews table (paper <-> reviewer users). */
    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reviews', 'paper_id', 'reviewer_id')
            ->withPivot(['status', 'recommendation', 'assigned_at', 'completed_at']);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EditorialNote::class)->latest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PaperStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /* --------------------------------- Scopes -------------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('papers.status', PaperStatus::Published->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('papers.title', 'like', $like)
                ->orWhere('papers.abstract', 'like', $like)
                ->orWhere('papers.reference', 'like', $like);
        });
    }

    /** Papers indexed under ANY of the given sector slugs. */
    public function scopeInSectors(Builder $query, array $slugs): Builder
    {
        $slugs = array_values(array_filter($slugs));
        if ($slugs === []) {
            return $query;
        }

        return $query->whereHas('sectors', fn (Builder $q) => $q->whereIn('sectors.slug', $slugs));
    }

    public function scopeInCommittee(Builder $query, ?string $slug): Builder
    {
        if (! $slug) {
            return $query;
        }

        return $query->whereHas('author.committee', fn (Builder $q) => $q->where('committees.slug', $slug));
    }

    /** Restrict a listing to what the given user is allowed to browse. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isEditor(), $user->isExecutive() => $query,
            $user->isAuthor() => $query->where('papers.user_id', $user->id),
            $user->isReviewer() => $query->whereHas('reviews', fn (Builder $q) => $q->where('reviewer_id', $user->id)),
            default => $query->published(),
        };
    }

    /* -------------------------------- Helpers -------------------------------- */

    /** Replace the paper's sector indexing; the first id becomes the primary sector. */
    public function syncSectors(array $sectorIds): void
    {
        $payload = collect($sectorIds)->unique()->values()
            ->mapWithKeys(fn ($id, $i) => [$id => ['is_primary' => $i === 0]])
            ->all();

        $this->sectors()->sync($payload);
    }

    public function hasFile(): bool
    {
        return ! empty($this->file_path);
    }

    public function isPublished(): bool
    {
        return $this->status === PaperStatus::Published;
    }

    public function downloadName(): string
    {
        $base = $this->reference ? $this->reference.'-' : '';

        return $base.Str::slug(Str::limit($this->title, 80, '')).'.pdf';
    }
}
