<?php

namespace Database\Factories;

use App\Models\Paper;
use App\Models\User;
use App\Support\SampleText;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\EditorialNote> */
class EditorialNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'paper_id' => Paper::factory(),
            'user_id' => User::factory()->editor(),
            'body' => SampleText::confidentialNote(),
        ];
    }
}
