<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use Database\Factories\Support\BelgianData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'brokerage_id' => fn (array $attributes) => Contract::query()->findOrFail($attributes['contract_id'])->brokerage_id,
            'declared_by' => function (array $attributes) {
                $contract = Contract::query()->findOrFail($attributes['contract_id']);

                return Client::query()->findOrFail($contract->client_id)->broker_id;
            },
            'reference' => sprintf('SIN-%s-%06d', now()->format('Y'), fake()->unique()->numberBetween(1, 999_999)),
            'occurred_on' => now()->subDays(fake()->numberBetween(1, 120))->startOfDay(),
            'description' => function (array $attributes) {
                $type = Contract::query()->findOrFail($attributes['contract_id'])->getRawOriginal('type');

                return fake()->randomElement(BelgianData::CLAIM_DESCRIPTIONS[$type]);
            },
            'status' => ClaimStatus::Open,
            'estimated_amount_cents' => fake()->numberBetween(20_000, 1_500_000),
            'settled_amount_cents' => null,
            'closed_at' => null,
        ];
    }

    public function inReview(): static
    {
        return $this->state(['status' => ClaimStatus::InReview]);
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClaimStatus::Settled,
            'settled_amount_cents' => $attributes['estimated_amount_cents'],
            'closed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ClaimStatus::Rejected, 'closed_at' => now()]);
    }
}
