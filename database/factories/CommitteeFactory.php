<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\SampleText;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Committee> */
class CommitteeFactory extends Factory
{
    public function definition(): array
    {
        $name = SampleText::uniqueName().' Committee';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => SampleText::description(),
        ];
    }
}
