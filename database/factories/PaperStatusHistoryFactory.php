<?php

namespace Database\Factories;

use App\Enums\PaperStatus;
use App\Models\Paper;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\PaperStatusHistory> */
class PaperStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'paper_id' => Paper::factory(),
            'user_id' => null,
            'from_status' => null,
            'to_status' => PaperStatus::Draft,
            'note' => null,
        ];
    }
}
