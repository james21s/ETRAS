<?php

namespace Database\Factories;

use App\Enums\PaperStatus;
use App\Enums\UserRole;
use App\Models\EditorialNote;
use App\Models\Paper;
use App\Models\PaperStatusHistory;
use App\Models\Review;
use App\Models\Sector;
use App\Models\User;
use App\Support\ReferenceNumber;
use App\Support\SampleText;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Paper> */
class PaperFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(SampleText::title(), '.');
        $created = Carbon::instance(fake()->dateTimeBetween('-60 days', '-1 day'));

        return [
            'user_id' => User::factory()->author(),
            'title' => $title,
            'slug' => Str::slug(Str::limit($title, 60, '')).'-'.Str::lower(Str::random(6)),
            'abstract' => SampleText::abstract(),
            'status' => PaperStatus::Draft,
            'version' => 1,
            'reference' => null,
            'file_path' => Paper::SEED_SAMPLE_PATH,
            'file_original_name' => Str::slug($title).'.pdf',
            'file_size' => fake()->numberBetween(150_000, 6_000_000),
            'file_mime' => 'application/pdf',
            'submitted_at' => null,
            'approved_at' => null,
            'published_at' => null,
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    /* ------------------------------ Status states ------------------------------ */

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PaperStatus::Draft]);
    }

    public function underReview(): static
    {
        return $this->state(function () {
            $submitted = Carbon::instance(fake()->dateTimeBetween('-120 days', '-4 days'));

            return [
                'status' => PaperStatus::UnderReview,
                'submitted_at' => $submitted,
                'created_at' => $submitted->copy()->subDays(fake()->numberBetween(1, 10)),
                'updated_at' => $submitted,
            ];
        });
    }

    public function approved(): static
    {
        return $this->state(function () {
            $submitted = Carbon::instance(fake()->dateTimeBetween('-200 days', '-60 days'));
            $approved = $submitted->copy()->addDays(fake()->numberBetween(15, 45));

            return [
                'status' => PaperStatus::Approved,
                'submitted_at' => $submitted,
                'approved_at' => $approved,
                'created_at' => $submitted->copy()->subDays(fake()->numberBetween(1, 10)),
                'updated_at' => $approved,
            ];
        });
    }

    public function published(): static
    {
        return $this->state(function () {
            $submitted = Carbon::instance(fake()->dateTimeBetween('-700 days', '-90 days'));
            $approved = $submitted->copy()->addDays(fake()->numberBetween(15, 50));
            $published = $approved->copy()->addDays(fake()->numberBetween(3, 20));

            return [
                'status' => PaperStatus::Published,
                'submitted_at' => $submitted,
                'approved_at' => $approved,
                'published_at' => $published,
                'reference' => ReferenceNumber::format($published->year, fake()->unique()->numberBetween(1, 9999)),
                'created_at' => $submitted->copy()->subDays(fake()->numberBetween(1, 10)),
                'updated_at' => $published,
            ];
        });
    }

    /* --------------------- Relationship generators (opt-in) --------------------- */

    /** Index the paper under 1-4 existing sectors (creates a few if none exist). */
    public function withSectors(int $min = 1, int $max = 4): static
    {
        return $this->afterCreating(function (Paper $paper) use ($min, $max) {
            if (Sector::count() === 0) {
                Sector::factory()->count(3)->create();
            }

            $ids = Sector::inRandomOrder()->limit(fake()->numberBetween($min, $max))->pluck('id')->all();
            $paper->syncSectors($ids);
        });
    }

    /** Create the audit trail, reviews and internal notes that match the paper's status. */
    public function withReviewHistory(): static
    {
        return $this->afterCreating(function (Paper $paper) {
            $editorId = User::where('role', UserRole::Editor->value)->inRandomOrder()->value('id');

            $trail = [[null, PaperStatus::Draft, $paper->created_at, $paper->user_id]];
            if ($paper->submitted_at) {
                $trail[] = [PaperStatus::Draft, PaperStatus::UnderReview, $paper->submitted_at, $paper->user_id];
            }
            if ($paper->approved_at) {
                $trail[] = [PaperStatus::UnderReview, PaperStatus::Approved, $paper->approved_at, $editorId];
            }
            if ($paper->published_at) {
                $trail[] = [PaperStatus::Approved, PaperStatus::Published, $paper->published_at, $editorId];
            }

            foreach ($trail as [$from, $to, $at, $userId]) {
                PaperStatusHistory::factory()->create([
                    'paper_id' => $paper->id,
                    'user_id' => $userId,
                    'from_status' => $from,
                    'to_status' => $to,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }

            if ($paper->status === PaperStatus::Draft) {
                return;
            }

            if (User::where('role', UserRole::Reviewer->value)->count() < 3) {
                User::factory()->count(3)->reviewer()->create();
            }

            $reviewerIds = User::where('role', UserRole::Reviewer->value)
                ->inRandomOrder()
                ->limit(fake()->numberBetween(2, 3))
                ->pluck('id');

            foreach ($reviewerIds as $reviewerId) {
                $assigned = $paper->submitted_at->copy()->addDays(fake()->numberBetween(1, 3));
                $completedAt = $assigned->copy()->addDays(fake()->numberBetween(4, 35));

                if ($paper->approved_at && $completedAt->gte($paper->approved_at)) {
                    $completedAt = $paper->approved_at->copy()->subDay();
                }

                $done = $paper->status !== PaperStatus::UnderReview
                    || ($completedAt->isPast() && fake()->boolean());

                $review = Review::factory()->state([
                    'paper_id' => $paper->id,
                    'reviewer_id' => $reviewerId,
                    'assigned_at' => $assigned,
                ]);

                ($done ? $review->completed($completedAt) : $review)->create();
            }

            if ($editorId) {
                EditorialNote::factory()
                    ->count(fake()->numberBetween(0, 2))
                    ->create(['paper_id' => $paper->id, 'user_id' => $editorId]);
            }
        });
    }
}
