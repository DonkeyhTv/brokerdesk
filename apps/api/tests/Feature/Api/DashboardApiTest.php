<?php

use App\Enums\ContractStatus;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function dashboardPortfolio(): Client
{
    $client = Client::factory()->create();
    $contract = fn (int $days, int $premium) => Contract::factory()->expiringInDays($days)->create(['client_id' => $client->id, 'annual_premium_cents' => $premium, 'type' => 'motor']);

    $contract(0, 100);
    $contract(30, 100);
    $contract(31, 100);
    $contract(60, 100);
    $contract(61, 100);
    $contract(90, 100);
    $contract(91, 400);

    Contract::factory()->expiringInDays(10)->withStatus(ContractStatus::Cancelled)->create(['client_id' => $client->id, 'annual_premium_cents' => 9999]);

    return $client;
}

it('splits renewals into the three windows, on active contracts only', function () {
    $client = dashboardPortfolio();
    Sanctum::actingAs($client->broker);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('expiring', ['within_30' => 2, 'from_31_to_60' => 2, 'from_61_to_90' => 2])
        ->assertJsonPath('active_contracts', 7)
        ->assertJsonPath('annual_premium_cents', 1000)
        ->assertJsonPath('premium_by_type', [['type' => 'motor', 'contracts' => 7, 'annual_premium_cents' => 1000]])
        ->assertJsonCount(5, 'next_expiries')
        ->assertJsonPath('next_expiries.0.client.id', $client->id);
});

it('counts unresolved claims only', function () {
    $claim = Claim::factory()->create();
    $contract = $claim->contract;
    Claim::factory()->inReview()->create(['contract_id' => $contract->id]);
    Claim::factory()->settled()->create(['contract_id' => $contract->id]);
    Claim::factory()->rejected()->create(['contract_id' => $contract->id]);
    Sanctum::actingAs($contract->client->broker);

    $this->getJson('/api/v1/dashboard')->assertJsonPath('open_claims', 2);
});

it('limits the figures to the portfolio of the broker, and the brokerage of the admin', function () {
    $client = dashboardPortfolio();
    $colleagueClient = Client::factory()->create(['brokerage_id' => $client->brokerage_id]);
    Contract::factory()->create(['client_id' => $colleagueClient->id]);
    Contract::factory()->create();

    Sanctum::actingAs($client->broker);
    $this->getJson('/api/v1/dashboard')->assertJsonPath('active_contracts', 7);

    Sanctum::actingAs(User::factory()->admin()->create(['brokerage_id' => $client->brokerage_id]));
    $this->getJson('/api/v1/dashboard')->assertJsonPath('active_contracts', 8);
});

it('answers with zeros on an empty portfolio', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('annual_premium_cents', 0)
        ->assertJsonPath('next_expiries', []);
});

it('keeps the dashboard away from portal accounts', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson('/api/v1/dashboard')->assertForbidden();
});
