<?php

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('exports what the user may see, with the list filters', function () {
    $client = Client::factory()->create(['first_name' => 'Zoé', 'last_name' => 'Mertens']);
    Contract::factory()->create(['client_id' => $client->id, 'policy_number' => 'BD-MINE', 'annual_premium_cents' => 123456]);
    Contract::factory()->withStatus(ContractStatus::Cancelled)->create(['client_id' => $client->id, 'policy_number' => 'BD-CANCELLED']);
    Contract::factory()->create(['policy_number' => 'BD-OTHER']);
    Sanctum::actingAs($client->broker);

    $response = $this->get('/api/v1/contracts/export?filter[status]=active')->assertOk();
    $csv = $response->streamedContent();

    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->toContain('"Policy number";Client;')
        ->toContain('BD-MINE;"Zoé Mertens"')
        ->toContain('1234,56')
        ->not->toContain('BD-CANCELLED')
        ->not->toContain('BD-OTHER');
});

it('neutralizes spreadsheet formulas', function () {
    $client = Client::factory()->create(['first_name' => '=HYPERLINK("http://evil")', 'last_name' => 'x']);
    Contract::factory()->create(['client_id' => $client->id]);
    Sanctum::actingAs($client->broker);

    expect($this->get('/api/v1/contracts/export')->streamedContent())
        ->toContain("'=HYPERLINK")
        ->not->toContain(';"=HYPERLINK');
});

it('keeps the export away from portal accounts and guests', function () {
    $this->getJson('/api/v1/contracts/export')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->client()->create());
    $this->getJson('/api/v1/contracts/export')->assertForbidden();
});

it('exports more rows than one chunk, each exactly once', function () {
    $client = Client::factory()->create();
    Contract::factory()->count(3)->expiringInDays(100)->create(['client_id' => $client->id]);
    Sanctum::actingAs($client->broker);

    $csv = $this->get('/api/v1/contracts/export?sort=expires_on')->streamedContent();

    expect(substr_count($csv, "\n"))->toBe(4);
});
