<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Author,
            'committee_id' => null,
            'organization' => fake()->company(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function executive(): static
    {
        return $this->state(fn () => ['role' => UserRole::Executive]);
    }

    public function editor(): static
    {
        return $this->state(fn () => ['role' => UserRole::Editor]);
    }

    public function reviewer(): static
    {
        return $this->state(fn () => ['role' => UserRole::Reviewer]);
    }

    public function author(): static
    {
        return $this->state(fn () => ['role' => UserRole::Author]);
    }

    public function subscriber(): static
    {
        return $this->state(fn () => ['role' => UserRole::Subscriber]);
    }
}
