<?php

namespace Database\Factories;

use App\Models\Brokerage;
use App\Support\EnterpriseNumber;
use Database\Factories\Support\BelgianData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brokerage>
 */
class BrokerageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Cabinet '.fake()->randomElement(BelgianData::LAST_NAMES).' '.fake()->unique()->numberBetween(1, 99_999),
            'enterprise_number' => EnterpriseNumber::fromBase(fake()->unique()->numberBetween(2_000_000, 19_999_999))->digits,
            'fsma_number' => (string) fake()->numberBetween(10_000, 99_999),
            'email' => fake()->unique()->userName().'@example.be',
            'phone' => BelgianData::phone(),
            ...BelgianData::address(),
        ];
    }
}
