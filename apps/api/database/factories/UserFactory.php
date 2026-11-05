<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Brokerage;
use App\Models\User;
use Database\Factories\Support\BelgianData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brokerage_id' => Brokerage::factory(),
            'name' => fake()->randomElement(BelgianData::FIRST_NAMES).' '.fake()->randomElement(BelgianData::LAST_NAMES),
            'email' => fake()->unique()->userName().'@example.be',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Broker,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function client(): static
    {
        return $this->state(['role' => UserRole::Client]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
