<?php

use App\Enums\UserRole;
use App\Models\Brokerage;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use App\Support\EnterpriseNumber;

it('does not allow mass assigning the role or the brokerage of a user', function () {
    $user = new User(['name' => 'Eve', 'role' => UserRole::Admin, 'brokerage_id' => 42]);

    expect($user->name)->toBe('Eve')
        ->and($user->role)->toBeNull()
        ->and($user->brokerage_id)->toBeNull();
});

it('does not allow mass assigning the owner of a client', function () {
    $client = new Client(['first_name' => 'Eve', 'brokerage_id' => 42, 'broker_id' => 42, 'user_id' => 42]);

    expect($client->first_name)->toBe('Eve')
        ->and($client->brokerage_id)->toBeNull()
        ->and($client->broker_id)->toBeNull()
        ->and($client->user_id)->toBeNull();
});

it('normalizes enterprise numbers when writing and casts them when reading', function () {
    $client = Client::factory()->company()->create(['enterprise_number' => 'BE0123.456.749']);

    expect($client->getRawOriginal('enterprise_number'))->toBe('0123456749')
        ->and($client->fresh()?->enterprise_number)->toBeInstanceOf(EnterpriseNumber::class)
        ->and((string) $client->fresh()?->enterprise_number)->toBe('0123.456.749');
});

it('refuses an invalid enterprise number before reaching the database', function () {
    Brokerage::factory()->create(['enterprise_number' => '0123456748']);
})->throws(InvalidArgumentException::class, 'check digits');

it('builds the search name from the client identity', function () {
    $individual = Client::factory()->create(['first_name' => 'Émilie', 'last_name' => 'Dubois']);
    $company = Client::factory()->company()->create(['company_name' => 'Garage Maes BV']);

    expect(trim($individual->fresh()?->search_name ?? ''))->toBe('émilie dubois')
        ->and(trim($company->fresh()?->search_name ?? ''))->toBe('garage maes bv');
});

it('connects a claim to its contract, client and brokerage', function () {
    $claim = Claim::factory()->create();
    $contract = $claim->contract;

    expect($contract->client->brokerage->is($claim->brokerage))->toBeTrue()
        ->and($contract->claims->first()?->is($claim))->toBeTrue()
        ->and($claim->declarer->is($contract->client->broker))->toBeTrue();
});

it('links a portal account to its client record', function () {
    $brokerage = Brokerage::factory()->create();
    $account = User::factory()->client()->for($brokerage)->create();
    $client = Client::factory()->create(['brokerage_id' => $brokerage->id, 'user_id' => $account->id]);

    expect($account->clientProfile?->is($client))->toBeTrue()
        ->and($client->user?->is($account))->toBeTrue()
        ->and($client->broker->portfolio->first()?->is($client))->toBeTrue();
});

it('hides documents from clients by default', function () {
    $document = Document::factory()->create();

    expect($document->fresh()?->visible_to_client)->toBeFalse()
        ->and($document->contract)->toBeInstanceOf(Contract::class)
        ->and($document->uploader)->toBeInstanceOf(User::class);
});
