<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Brokerage;
use App\Models\Client;
use App\Models\User;
use App\Support\EnterpriseNumber;
use Database\Factories\Support\BelgianData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->randomElement(BelgianData::FIRST_NAMES);
        $lastName = fake()->randomElement(BelgianData::LAST_NAMES);

        return [
            'brokerage_id' => Brokerage::factory(),
            'broker_id' => fn (array $attributes) => User::factory()->state(['brokerage_id' => $attributes['brokerage_id']]),
            'user_id' => null,
            'type' => ClientType::Individual,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company_name' => null,
            'enterprise_number' => null,
            'email' => Str::slug($firstName.' '.$lastName, '.').'.'.fake()->unique()->numberBetween(1, 999_999).'@example.be',
            'phone' => BelgianData::phone(),
            ...BelgianData::address(),
        ];
    }

    public function company(): static
    {
        return $this->state(function () {
            $name = BelgianData::companyName();

            return [
                'type' => ClientType::Company,
                'first_name' => null,
                'last_name' => null,
                'company_name' => $name,
                'enterprise_number' => EnterpriseNumber::fromBase(fake()->unique()->numberBetween(2_000_000, 19_999_999))->digits,
                'email' => 'info.'.Str::slug($name, '.').'.'.fake()->unique()->numberBetween(1, 999_999).'@example.be',
            ];
        });
    }
}
