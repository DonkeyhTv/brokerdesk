<?php

use App\Enums\ClaimStatus;
use App\Enums\ContractStatus;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Insurer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function contractPayload(array $overrides = []): array
{
    return [
        'insurer_id' => Insurer::factory()->create()->id,
        'policy_number' => 'BD-2026-1',
        'type' => 'motor',
        'status' => 'active',
        'starts_on' => '2026-01-01',
        'expires_on' => '2027-01-01',
        'annual_premium_cents' => 85000,
        'payment_frequency' => 'annual',
        ...$overrides,
    ];
}

it('lets a broker create a contract in their portfolio only', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create(['brokerage_id' => $client->brokerage_id]);
    Sanctum::actingAs($client->broker);

    $this->postJson('/api/v1/contracts', contractPayload(['client_id' => $otherClient->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('client_id');

    $this->postJson('/api/v1/contracts', contractPayload(['client_id' => $client->id]))
        ->assertCreated()
        ->assertJsonPath('data.client.id', $client->id)
        ->assertJsonPath('data.notice_deadline', '2026-10-01');

    expect(Contract::query()->sole()->brokerage_id)->toBe($client->brokerage_id);
});

it('refuses contract creation by a portal account', function () {
    $account = User::factory()->client()->create();
    $client = Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);
    Sanctum::actingAs($account);

    $this->postJson('/api/v1/contracts', contractPayload(['client_id' => $client->id]))->assertForbidden();
});

it('validates contract data', function (array $overrides, string $field) {
    $client = Client::factory()->create();
    Sanctum::actingAs($client->broker);

    $this->postJson('/api/v1/contracts', contractPayload(['client_id' => $client->id, ...$overrides]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'dates in the wrong order' => [['expires_on' => '2025-01-01'], 'expires_on'],
    'negative premium' => [['annual_premium_cents' => -1], 'annual_premium_cents'],
    'unknown type' => [['type' => 'pet'], 'type'],
    'unknown insurer' => [['insurer_id' => 999999], 'insurer_id'],
]);

it('refuses a policy number already used in the brokerage', function () {
    $contract = Contract::factory()->create(['policy_number' => 'BD-1']);
    Sanctum::actingAs($contract->client->broker);

    $this->postJson('/api/v1/contracts', contractPayload(['client_id' => $contract->client_id, 'policy_number' => 'BD-1']))
        ->assertJsonValidationErrors('policy_number');

    $this->putJson("/api/v1/contracts/{$contract->id}", contractPayload(['policy_number' => 'BD-1']))->assertOk();
});

it('does not move a contract to another client on update', function () {
    $contract = Contract::factory()->create();
    $other = Client::factory()->create();
    $originalClient = $contract->client_id;
    Sanctum::actingAs($contract->client->broker);

    $this->putJson("/api/v1/contracts/{$contract->id}", contractPayload(['client_id' => $other->id]))->assertOk();

    expect($contract->fresh()?->client_id)->toBe($originalClient);
});

it('shows clients their own contracts only', function () {
    $account = User::factory()->client()->create();
    $client = Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);
    $mine = Contract::factory()->create(['client_id' => $client->id]);
    $other = Contract::factory()->create(['client_id' => Client::factory()->create(['brokerage_id' => $account->brokerage_id])->id]);
    Sanctum::actingAs($account);

    $this->getJson('/api/v1/contracts')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
    $this->getJson("/api/v1/contracts/{$mine->id}")->assertOk();
    $this->getJson("/api/v1/contracts/{$other->id}")->assertNotFound();
    $this->putJson("/api/v1/contracts/{$mine->id}", contractPayload())->assertForbidden();
    $this->deleteJson("/api/v1/contracts/{$mine->id}")->assertForbidden();
});

it('filters on the renewal window', function () {
    $client = Client::factory()->create();
    $soon = Contract::factory()->expiringInDays(20)->create(['client_id' => $client->id]);
    Contract::factory()->expiringInDays(80)->create(['client_id' => $client->id]);
    Contract::factory()->expiringInDays(20)->withStatus(ContractStatus::Cancelled)->create(['client_id' => $client->id]);
    Contract::factory()->expiringInDays(-5)->create(['client_id' => $client->id]);
    Sanctum::actingAs($client->broker);

    $this->getJson('/api/v1/contracts?filter[expires_within]=30')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $soon->id);
});

it('searches on policy number and client name, and sorts', function () {
    $broker = User::factory()->create();
    $client = Client::factory()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id, 'first_name' => 'Zoé', 'last_name' => 'Mertens']);
    $cheap = Contract::factory()->create(['client_id' => $client->id, 'policy_number' => 'ABC-1', 'annual_premium_cents' => 100]);
    $costly = Contract::factory()->create(['client_id' => $client->id, 'policy_number' => 'XYZ-2', 'annual_premium_cents' => 900]);
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/contracts?filter[search]=abc')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $cheap->id);
    $this->getJson('/api/v1/contracts?filter[search]=zoe')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/contracts?sort=-annual_premium_cents')->assertJsonPath('data.0.id', $costly->id);
});

it('refuses to delete a contract with unresolved claims, then soft deletes', function () {
    $claim = Claim::factory()->create();
    $contract = $claim->contract;
    Sanctum::actingAs($contract->client->broker);

    $this->deleteJson("/api/v1/contracts/{$contract->id}")->assertConflict();

    $claim->forceFill(['status' => ClaimStatus::Rejected, 'closed_at' => now()])->save();

    $this->deleteJson("/api/v1/contracts/{$contract->id}")->assertNoContent();
    $this->assertSoftDeleted($contract);
});
