<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\PaymentFrequency;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Insurer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expiresOn = now()->addDays(fake()->numberBetween(91, 360))->startOfDay();

        return [
            'client_id' => Client::factory(),
            'brokerage_id' => fn (array $attributes) => Client::query()->findOrFail($attributes['client_id'])->brokerage_id,
            'insurer_id' => Insurer::factory(),
            'policy_number' => sprintf('BD-%s-%07d', $expiresOn->format('y'), fake()->unique()->numberBetween(1, 9_999_999)),
            'type' => fake()->randomElement(ContractType::cases()),
            'status' => ContractStatus::Active,
            'starts_on' => $expiresOn->copy()->subYear(),
            'expires_on' => $expiresOn,
            'annual_premium_cents' => fake()->numberBetween(18_000, 320_000),
            'payment_frequency' => fake()->randomElement(PaymentFrequency::cases()),
        ];
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(function () use ($days) {
            $expiresOn = now()->addDays($days)->startOfDay();

            return [
                'starts_on' => $expiresOn->copy()->subYear(),
                'expires_on' => $expiresOn,
            ];
        });
    }

    public function withStatus(ContractStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
