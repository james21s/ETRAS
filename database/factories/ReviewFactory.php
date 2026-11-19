<?php

namespace Database\Factories;

use App\Models\Paper;
use App\Models\Review;
use App\Models\User;
use App\Support\SampleText;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'paper_id' => Paper::factory(),
            'reviewer_id' => User::factory()->reviewer(),
            'status' => Review::PENDING,
            'recommendation' => null,
            'score' => null,
            'comments_to_author' => null,
            'confidential_notes' => null,
            'assigned_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'completed_at' => null,
        ];
    }

    public function completed(?DateTimeInterface $at = null): static
    {
        return $this->state(fn () => [
            'status' => Review::COMPLETED,
            'recommendation' => fake()->randomElement(array_keys(Review::RECOMMENDATIONS)),
            'score' => fake()->numberBetween(1, 5),
            'comments_to_author' => SampleText::reviewComments(),
            'confidential_notes' => SampleText::confidentialNote(),
            'completed_at' => $at ?? now(),
        ]);
    }
}
