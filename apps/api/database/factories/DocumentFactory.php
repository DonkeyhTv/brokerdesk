<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'uploaded_by' => function (array $attributes) {
                $contract = Contract::query()->findOrFail($attributes['contract_id']);

                return Client::query()->findOrFail($contract->client_id)->broker_id;
            },
            'original_name' => 'conditions-particulieres.pdf',
            'path' => 'documents/'.Str::uuid().'.pdf',
            'size_bytes' => fake()->numberBetween(20_000, 2_000_000),
            'mime_type' => 'application/pdf',
        ];
    }
}
