<?php

namespace Database\Factories;

use App\Models\Insurer;
use Database\Factories\Support\BelgianData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insurer>
 */
class InsurerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(BelgianData::INSURERS).' '.fake()->unique()->numberBetween(1, 99_999),
        ];
    }
}
