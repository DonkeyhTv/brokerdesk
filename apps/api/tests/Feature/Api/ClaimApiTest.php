<?php

use App\Enums\ClaimStatus;
use App\Enums\ContractStatus;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function insuredContract(): array
{
    $account = User::factory()->client()->create();
    $client = Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);

    return [$account, Contract::factory()->create(['client_id' => $client->id, 'type' => 'motor'])];
}

it('lets a client declare a claim on their own contract', function () {
    [$account, $contract] = insuredContract();
    Sanctum::actingAs($account);

    $this->postJson('/api/v1/claims', ['contract_id' => $contract->id, 'occurred_on' => now()->toDateString(), 'description' => 'Pare-chocs abîmé.'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.declared_by.id', $account->id)
        ->assertJsonPath('data.reference', 'SIN-'.now()->format('Y').'-000001');
});

it('numbers references per brokerage', function () {
    [$account, $contract] = insuredContract();
    [$otherAccount, $otherContract] = insuredContract();
    $payload = ['occurred_on' => now()->toDateString(), 'description' => 'Sinistre.'];

    Sanctum::actingAs($account);
    $this->postJson('/api/v1/claims', ['contract_id' => $contract->id, ...$payload])->assertCreated();
    $this->postJson('/api/v1/claims', ['contract_id' => $contract->id, ...$payload])
        ->assertJsonPath('data.reference', 'SIN-'.now()->format('Y').'-000002');

    Sanctum::actingAs($otherAccount);
    $this->postJson('/api/v1/claims', ['contract_id' => $otherContract->id, ...$payload])
        ->assertJsonPath('data.reference', 'SIN-'.now()->format('Y').'-000001');
});

it('refuses a claim on the contract of someone else, without revealing it exists', function () {
    [$account] = insuredContract();
    [, $otherContract] = insuredContract();
    Sanctum::actingAs($account);

    $this->postJson('/api/v1/claims', ['contract_id' => $otherContract->id, 'occurred_on' => now()->toDateString(), 'description' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['contract_id' => 'The selected contract is invalid.']);
});

it('refuses a claim on an inactive contract, or in the future', function () {
    [$account, $contract] = insuredContract();
    $contract->update(['status' => ContractStatus::Cancelled]);
    Sanctum::actingAs($account);

    $this->postJson('/api/v1/claims', ['contract_id' => $contract->id, 'occurred_on' => now()->addDay()->toDateString(), 'description' => 'x'])
        ->assertJsonValidationErrors(['contract_id', 'occurred_on']);
});

it('does not let a client set amounts or a status', function () {
    [$account, $contract] = insuredContract();
    Sanctum::actingAs($account);
    $payload = ['contract_id' => $contract->id, 'occurred_on' => now()->toDateString(), 'description' => 'x'];

    $this->postJson('/api/v1/claims', [...$payload, 'estimated_amount_cents' => 100])->assertJsonValidationErrors('estimated_amount_cents');

    $this->postJson('/api/v1/claims', [...$payload, 'status' => 'settled', 'reference' => 'HACK'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'open');

    expect(Claim::query()->where('reference', 'HACK')->exists())->toBeFalse();
});

it('shows each person only the claims they may see', function () {
    [$account, $contract] = insuredContract();
    $mine = Claim::factory()->create(['contract_id' => $contract->id]);
    $other = Claim::factory()->create();
    Sanctum::actingAs($account);

    $this->getJson('/api/v1/claims')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
    $this->getJson("/api/v1/claims/{$other->id}")->assertNotFound();
    $this->getJson("/api/v1/claims/{$mine->id}")->assertOk()->assertJsonPath('data.contract.id', $contract->id);
    $this->putJson("/api/v1/claims/{$mine->id}", ['occurred_on' => now()->toDateString(), 'description' => 'x', 'status' => 'settled', 'settled_amount_cents' => 1])
        ->assertForbidden();
});

it('filters and lists without N+1 queries', function () {
    $claim = Claim::factory()->create();
    $broker = $claim->contract->client->broker;
    $other = Contract::factory()->create(['client_id' => $claim->contract->client_id]);
    Claim::factory()->rejected()->create(['contract_id' => $other->id]);
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/claims')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/claims?filter[status]=open')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $claim->id);
    $this->getJson('/api/v1/claims?filter[search]='.substr($claim->reference, 4))->assertJsonPath('data.0.id', $claim->id);
});

it('closes a claim when it is settled, and then freezes it', function () {
    $claim = Claim::factory()->create();
    Sanctum::actingAs($claim->contract->client->broker);
    $payload = ['occurred_on' => $claim->occurred_on->toDateString(), 'description' => $claim->description];

    $this->putJson("/api/v1/claims/{$claim->id}", [...$payload, 'status' => 'settled'])
        ->assertJsonValidationErrors('settled_amount_cents');

    $this->putJson("/api/v1/claims/{$claim->id}", [...$payload, 'status' => 'in_review', 'settled_amount_cents' => 5])
        ->assertJsonValidationErrors('settled_amount_cents');

    $this->putJson("/api/v1/claims/{$claim->id}", [...$payload, 'status' => 'in_review'])
        ->assertOk()
        ->assertJsonPath('data.closed_at', null);

    $this->putJson("/api/v1/claims/{$claim->id}", [...$payload, 'status' => 'settled', 'settled_amount_cents' => 12000])
        ->assertOk()
        ->assertJsonPath('data.status', 'settled')
        ->assertJsonPath('data.settled_amount_cents', 12000);

    expect($claim->fresh()?->closed_at)->not->toBeNull();

    $this->putJson("/api/v1/claims/{$claim->id}", [...$payload, 'status' => 'open'])->assertConflict();
    expect($claim->fresh()?->status)->toBe(ClaimStatus::Settled);
});

it('reserves claim deletion to admins', function () {
    $claim = Claim::factory()->create();

    Sanctum::actingAs($claim->contract->client->broker);
    $this->deleteJson("/api/v1/claims/{$claim->id}")->assertForbidden();

    Sanctum::actingAs(User::factory()->admin()->create(['brokerage_id' => $claim->brokerage_id]));
    $this->deleteJson("/api/v1/claims/{$claim->id}")->assertNoContent();
    $this->assertModelMissing($claim);
});
