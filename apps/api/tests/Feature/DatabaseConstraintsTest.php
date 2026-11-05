<?php

use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('refuses a client whose broker belongs to another brokerage', function () {
    $client = Client::factory()->create();

    Client::factory()->create([
        'brokerage_id' => $client->brokerage_id,
        'broker_id' => User::factory()->create()->id,
    ]);
})->throws(QueryException::class, 'clients_broker_id_brokerage_id_foreign');

it('refuses a contract whose client belongs to another brokerage', function () {
    $contract = Contract::factory()->create();
    $otherClient = Client::factory()->create();

    DB::table('contracts')->where('id', $contract->id)->update(['client_id' => $otherClient->id]);
})->throws(QueryException::class, 'contracts_client_id_brokerage_id_foreign');

it('refuses a claim whose contract belongs to another brokerage', function () {
    $claim = Claim::factory()->create();
    $otherContract = Contract::factory()->create();

    DB::table('claims')->where('id', $claim->id)->update(['contract_id' => $otherContract->id]);
})->throws(QueryException::class, 'claims_contract_id_brokerage_id_foreign');

it('refuses an individual with company details', function () {
    Client::factory()->create(['company_name' => 'Garage Dupont SRL']);
})->throws(QueryException::class, 'clients_identity_matches_type');

it('refuses a company without an enterprise number', function () {
    Client::factory()->company()->create(['enterprise_number' => null]);
})->throws(QueryException::class, 'clients_identity_matches_type');

it('refuses an invalid postal code', function () {
    Client::factory()->create(['postal_code' => '0999']);
})->throws(QueryException::class, 'clients_postal_code_format');

it('refuses a contract that expires before it starts', function () {
    Contract::factory()->create(['starts_on' => '2026-06-01', 'expires_on' => '2026-01-01']);
})->throws(QueryException::class, 'contracts_dates_ordered');

it('refuses a negative premium', function () {
    Contract::factory()->create(['annual_premium_cents' => -1]);
})->throws(QueryException::class, 'contracts_premium_positive');

it('refuses a closed claim without a closing date', function () {
    Claim::factory()->settled()->create(['closed_at' => null]);
})->throws(QueryException::class, 'claims_closed_matches_status');

it('refuses an open claim with a closing date', function () {
    Claim::factory()->create(['closed_at' => now()]);
})->throws(QueryException::class, 'claims_closed_matches_status');

it('allows the same policy number in two brokerages but not in one', function () {
    $contract = Contract::factory()->create(['policy_number' => 'BD-1']);

    expect(Contract::factory()->create(['policy_number' => 'BD-1'])->exists)->toBeTrue();

    Contract::factory()->create([
        'client_id' => $contract->client_id,
        'policy_number' => 'BD-1',
    ]);
})->throws(QueryException::class, 'contracts_brokerage_id_policy_number_unique');

it('frees an enterprise number once its client is soft deleted', function () {
    $client = Client::factory()->company()->create();
    $attributes = ['brokerage_id' => $client->brokerage_id, 'broker_id' => $client->broker_id, 'enterprise_number' => $client->enterprise_number];

    // The savepoint keeps the test's transaction usable after the expected failure.
    expect(fn () => DB::transaction(fn () => Client::factory()->company()->create($attributes)))
        ->toThrow(QueryException::class, 'clients_brokerage_enterprise_number_unique');

    $client->delete();

    expect(Client::factory()->company()->create($attributes)->exists)->toBeTrue();
});
