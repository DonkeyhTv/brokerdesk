<?php

use App\Enums\ContractStatus;
use App\Models\Brokerage;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function individualPayload(array $overrides = []): array
{
    return [
        'type' => 'individual',
        'first_name' => 'Émilie',
        'last_name' => 'Lefèvre',
        'email' => 'emilie@example.be',
        'phone' => '+32 470 00 00 01',
        'street' => 'Rue de la Station 1',
        'postal_code' => '4000',
        'city' => 'Liège',
        ...$overrides,
    ];
}

function companyPayload(array $overrides = []): array
{
    return [
        'type' => 'company',
        'company_name' => 'Garage Maes BV',
        'enterprise_number' => 'BE0123.456.749',
        'email' => 'info@maes.example.be',
        'street' => 'Kerkstraat 1',
        'postal_code' => '9000',
        'city' => 'Gent',
        ...$overrides,
    ];
}

it('requires authentication', function () {
    $this->getJson('/api/v1/clients')->assertUnauthorized();
});

it('lists only the portfolio of a broker', function () {
    $broker = User::factory()->create();
    $mine = Client::factory()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id]);
    Client::factory()->create(['brokerage_id' => $broker->brokerage_id]);
    Client::factory()->create();
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/clients')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);
});

it('lists the whole brokerage for an admin, without N+1 queries', function () {
    $admin = User::factory()->admin()->create();
    Client::factory()->count(3)->create(['brokerage_id' => $admin->brokerage_id]);
    Client::factory()->create();
    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/clients')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'broker' => ['id', 'name'], 'contracts_count']], 'meta' => ['total', 'current_page'], 'links']);
});

it('keeps the client list away from portal accounts', function () {
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson('/api/v1/clients')->assertForbidden();
});

it('finds clients ignoring accents and case', function (string $term) {
    $broker = User::factory()->create();
    $owner = ['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id];
    $match = Client::factory()->create([...$owner, 'first_name' => 'Émilie', 'last_name' => 'Lefèvre']);
    Client::factory()->create([...$owner, 'first_name' => 'Noah', 'last_name' => 'Maes']);
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/clients?filter[search]='.urlencode($term))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
})->with(['lefevre', 'LEFÈVRE', 'emilie']);

it('finds a company by its enterprise number', function () {
    $broker = User::factory()->create();
    $company = Client::factory()->company()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id, 'enterprise_number' => '0123456749']);
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/clients?filter[search]=0123.456')->assertJsonPath('data.0.id', $company->id);
});

it('treats LIKE wildcards in a search as plain text', function () {
    $broker = User::factory()->create();
    Client::factory()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id]);
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/clients?filter[search]='.urlencode('%'))->assertJsonCount(0, 'data');
});

it('filters, sorts and paginates', function () {
    $broker = User::factory()->create();
    $owner = ['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id];
    Client::factory()->create([...$owner, 'city' => 'Namur']);
    Client::factory()->create([...$owner, 'city' => 'Arlon']);
    $company = Client::factory()->company()->create([...$owner, 'city' => 'Gent']);
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/clients?filter[type]=company')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $company->id);
    $this->getJson('/api/v1/clients?sort=city')->assertJsonPath('data.0.city', 'Arlon');
    $this->getJson('/api/v1/clients?sort=-city')->assertJsonPath('data.0.city', 'Namur');
    $this->getJson('/api/v1/clients?per_page=2&page=2')->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 3);
});

it('refuses unknown filters and sorts', function (string $query) {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/clients?'.$query)->assertStatus(400);
})->with(['filter[password]=x', 'sort=password']);

it('creates an individual in the portfolio of the broker', function () {
    $broker = User::factory()->create();
    Sanctum::actingAs($broker);

    $this->postJson('/api/v1/clients', individualPayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'Émilie Lefèvre')
        ->assertJsonPath('data.broker.id', $broker->id);

    expect(Client::query()->sole()->brokerage_id)->toBe($broker->brokerage_id);
});

it('normalizes the enterprise number of a company', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/clients', companyPayload())
        ->assertCreated()
        ->assertJsonPath('data.enterprise_number', '0123.456.749');
});

it('validates client data', function (array $overrides, string $field) {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/clients', companyPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'bad check digits' => [['enterprise_number' => '0123456748'], 'enterprise_number'],
    'missing number' => [['enterprise_number' => null], 'enterprise_number'],
    'bad postal code' => [['postal_code' => '999'], 'postal_code'],
    'bad email' => [['email' => 'nope'], 'email'],
    'bad type' => [['type' => 'alien'], 'type'],
    'person fields on a company' => [['first_name' => 'Eve'], 'first_name'],
]);

it('refuses a duplicate enterprise number in a brokerage, but not in another', function () {
    $broker = User::factory()->create();
    Client::factory()->company()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id, 'enterprise_number' => '0123456749']);
    Sanctum::actingAs($broker);

    $this->postJson('/api/v1/clients', companyPayload())->assertUnprocessable()->assertJsonValidationErrors('enterprise_number');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/v1/clients', companyPayload())->assertCreated();
});

it('ignores ownership fields sent by a broker, and refuses to reassign', function () {
    $broker = User::factory()->create();
    Sanctum::actingAs($broker);

    $this->postJson('/api/v1/clients', individualPayload(['broker_id' => User::factory()->create(['brokerage_id' => $broker->brokerage_id])->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('broker_id');

    $this->postJson('/api/v1/clients', individualPayload(['brokerage_id' => 999, 'user_id' => 999]))
        ->assertCreated();

    expect(Client::query()->sole())
        ->brokerage_id->toBe($broker->brokerage_id)
        ->user_id->toBeNull();
});

it('lets an admin assign a broker of the brokerage only', function () {
    $admin = User::factory()->admin()->create();
    $colleague = User::factory()->create(['brokerage_id' => $admin->brokerage_id]);
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/clients', individualPayload(['broker_id' => User::factory()->create()->id]))
        ->assertJsonValidationErrors('broker_id');

    $this->postJson('/api/v1/clients', individualPayload(['broker_id' => $colleague->id]))
        ->assertCreated()
        ->assertJsonPath('data.broker.id', $colleague->id);
});

it('clears the old identity when the type changes', function () {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->create(['brokerage_id' => $admin->brokerage_id]);
    Sanctum::actingAs($admin);

    $this->putJson("/api/v1/clients/{$client->id}", companyPayload(['broker_id' => $client->broker_id]))
        ->assertOk()
        ->assertJsonPath('data.first_name', null)
        ->assertJsonPath('data.company_name', 'Garage Maes BV');
});

it('hides other portfolios behind a 404 and lets clients read only themselves', function () {
    $brokerage = Brokerage::factory()->create();
    $account = User::factory()->client()->for($brokerage)->create();
    $client = Client::factory()->create(['brokerage_id' => $brokerage->id, 'user_id' => $account->id]);

    foreach ([User::factory()->create(['brokerage_id' => $brokerage->id]), User::factory()->admin()->create()] as $stranger) {
        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/clients/{$client->id}")->assertNotFound();
        $this->putJson("/api/v1/clients/{$client->id}", individualPayload())->assertNotFound();
        $this->deleteJson("/api/v1/clients/{$client->id}")->assertNotFound();
    }

    Sanctum::actingAs($account);
    $this->getJson("/api/v1/clients/{$client->id}")->assertOk();
    $this->putJson("/api/v1/clients/{$client->id}", individualPayload())->assertForbidden();
    $this->deleteJson("/api/v1/clients/{$client->id}")->assertForbidden();
});

it('refuses to delete a client with active contracts, then soft deletes', function () {
    $contract = Contract::factory()->create();
    $client = $contract->client;
    Sanctum::actingAs($client->broker);

    $this->deleteJson("/api/v1/clients/{$client->id}")->assertConflict();

    $contract->update(['status' => ContractStatus::Cancelled]);

    $this->deleteJson("/api/v1/clients/{$client->id}")->assertNoContent();
    $this->assertSoftDeleted($client);
    $this->getJson("/api/v1/clients/{$client->id}")->assertNotFound();
});
