<?php

use App\Enums\ClaimStatus;
use App\Models\Brokerage;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/*
 * One portfolio, surrounded by everyone who might try to reach it. Every actor
 * except "admin", "broker" and "owner" must be treated as a stranger.
 */
function world(): object
{
    $brokerage = Brokerage::factory()->create();
    $broker = User::factory()->for($brokerage)->create();
    $owner = User::factory()->client()->for($brokerage)->create();
    $client = Client::factory()->create(['brokerage_id' => $brokerage->id, 'broker_id' => $broker->id, 'user_id' => $owner->id]);
    $contract = Contract::factory()->create(['client_id' => $client->id]);

    return (object) [
        'client' => $client,
        'contract' => $contract,
        'claim' => Claim::factory()->create(['contract_id' => $contract->id]),
        'admin' => User::factory()->admin()->for($brokerage)->create(),
        'broker' => $broker,
        'owner' => $owner,
        'colleague' => User::factory()->for($brokerage)->create(),
        'otherClient' => User::factory()->client()->for($brokerage)->create(),
        'otherAdmin' => User::factory()->admin()->create(),
        'otherBroker' => User::factory()->create(),
    ];
}

function allowedActors(object $world, string $ability, mixed $arguments): array
{
    $actors = ['admin', 'broker', 'owner', 'colleague', 'otherClient', 'otherAdmin', 'otherBroker'];

    return array_values(array_filter($actors, fn (string $actor) => Gate::forUser($world->{$actor})->allows($ability, $arguments)));
}

it('protects clients', function (string $ability, array $expected) {
    $world = world();

    expect(allowedActors($world, $ability, $world->client))->toBe($expected);
})->with([
    'view' => ['view', ['admin', 'broker', 'owner']],
    'update' => ['update', ['admin', 'broker']],
    'delete' => ['delete', ['admin', 'broker']],
]);

it('protects contracts', function (string $ability, array $expected) {
    $world = world();

    expect(allowedActors($world, $ability, $world->contract))->toBe($expected);
})->with([
    'view' => ['view', ['admin', 'broker', 'owner']],
    'update' => ['update', ['admin', 'broker']],
    'delete' => ['delete', ['admin', 'broker']],
]);

it('protects claims', function (string $ability, array $expected) {
    $world = world();

    expect(allowedActors($world, $ability, $world->claim))->toBe($expected);
})->with([
    'view' => ['view', ['admin', 'broker', 'owner']],
    'update' => ['update', ['admin', 'broker']],
    'delete' => ['delete', ['admin']],
]);

it('lets only the people in charge create records in a portfolio', function () {
    $world = world();

    expect(allowedActors($world, 'create', [Contract::class, $world->client]))->toBe(['admin', 'broker'])
        ->and(allowedActors($world, 'create', [Document::class, $world->contract]))->toBe(['admin', 'broker']);
});

it('lets the insured declare a claim on their own contract', function () {
    $world = world();

    expect(allowedActors($world, 'create', [Claim::class, $world->contract]))->toBe(['admin', 'broker', 'owner']);
});

it('only shows a client the documents marked as visible', function () {
    $world = world();
    $hidden = Document::factory()->create(['contract_id' => $world->contract->id]);
    $visible = Document::factory()->create(['contract_id' => $world->contract->id, 'visible_to_client' => true]);

    expect(allowedActors($world, 'view', $hidden))->toBe(['admin', 'broker'])
        ->and(allowedActors($world, 'view', $visible))->toBe(['admin', 'broker', 'owner'])
        ->and(allowedActors($world, 'update', $visible))->toBe(['admin', 'broker'])
        ->and(allowedActors($world, 'delete', $visible))->toBe(['admin', 'broker']);
});

it('keeps client lists to staff, and lets everyone ask for contracts and claims', function () {
    $world = world();

    expect(Gate::forUser($world->broker)->allows('viewAny', Client::class))->toBeTrue()
        ->and(Gate::forUser($world->owner)->allows('viewAny', Client::class))->toBeFalse()
        ->and(Gate::forUser($world->owner)->allows('create', Client::class))->toBeFalse()
        ->and(Gate::forUser($world->owner)->allows('viewAny', Contract::class))->toBeTrue()
        ->and(Gate::forUser($world->owner)->allows('viewAny', Claim::class))->toBeTrue();
});

it('filters lists to what each actor may see', function () {
    $world = world();
    $hidden = Document::factory()->create(['contract_id' => $world->contract->id]);
    $visible = Document::factory()->create(['contract_id' => $world->contract->id, 'visible_to_client' => true]);

    $seenBy = fn (User $user, string $model) => $model::query()->visibleTo($user)->pluck('id')->all();

    foreach (['admin', 'broker', 'owner'] as $actor) {
        expect($seenBy($world->{$actor}, Client::class))->toBe([$world->client->id])
            ->and($seenBy($world->{$actor}, Contract::class))->toBe([$world->contract->id])
            ->and($seenBy($world->{$actor}, Claim::class))->toBe([$world->claim->id]);
    }

    foreach (['colleague', 'otherClient', 'otherAdmin', 'otherBroker'] as $stranger) {
        expect($seenBy($world->{$stranger}, Client::class))->toBeEmpty()
            ->and($seenBy($world->{$stranger}, Contract::class))->toBeEmpty()
            ->and($seenBy($world->{$stranger}, Claim::class))->toBeEmpty()
            ->and($seenBy($world->{$stranger}, Document::class))->toBeEmpty();
    }

    expect($seenBy($world->broker, Document::class))->toEqualCanonicalizing([$hidden->id, $visible->id])
        ->and($seenBy($world->owner, Document::class))->toBe([$visible->id]);
});

it('lets an admin see the whole brokerage, but not another one', function () {
    $world = world();
    $colleagueClient = Client::factory()->create(['brokerage_id' => $world->client->brokerage_id, 'broker_id' => $world->colleague->id]);
    Client::factory()->create();

    expect(Client::query()->visibleTo($world->admin)->pluck('id')->all())
        ->toEqualCanonicalizing([$world->client->id, $colleagueClient->id]);
});

it('keeps claims reachable after their contract and client are soft deleted', function () {
    $world = world();
    $world->contract->delete();
    $world->client->delete();

    $claim = Claim::query()->findOrFail($world->claim->id);

    expect(Gate::forUser($world->admin)->allows('view', $claim))->toBeTrue()
        ->and(Gate::forUser($world->otherAdmin)->allows('view', $claim))->toBeFalse()
        ->and(Claim::query()->visibleTo($world->broker)->pluck('id')->all())->toBe([$claim->id])
        ->and($claim->status)->toBe(ClaimStatus::Open);
});
