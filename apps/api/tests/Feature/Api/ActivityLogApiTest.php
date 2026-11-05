<?php

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function clientPayloadFor(Client $client, array $overrides = []): array
{
    return [
        'type' => 'individual',
        'first_name' => $client->first_name,
        'last_name' => $client->last_name,
        'email' => $client->email,
        'phone' => $client->phone,
        'street' => $client->street,
        'postal_code' => $client->postal_code,
        'city' => $client->city,
        ...$overrides,
    ];
}

it('records who changed what, with the old and new values', function () {
    $client = Client::factory()->create(['city' => 'Namur']);
    $broker = $client->broker;
    Sanctum::actingAs($broker);

    $this->putJson("/api/v1/clients/{$client->id}", clientPayloadFor($client, ['city' => 'Liège']))->assertOk();

    $log = ActivityLog::query()->where('action', 'updated')->sole();

    expect($log->user_id)->toBe($broker->id)
        ->and($log->brokerage_id)->toBe($client->brokerage_id)
        ->and($log->subject_type)->toBe('client')
        ->and($log->subject_id)->toBe($client->id)
        ->and($log->subject_label)->toBe($client->displayName())
        ->and($log->changes)->toEqual(['city' => ['old' => 'Namur', 'new' => 'Liège']]);
});

it('records nothing when a save changes nothing', function () {
    $client = Client::factory()->create();
    Sanctum::actingAs($client->broker);

    $this->putJson("/api/v1/clients/{$client->id}", clientPayloadFor($client))->assertOk();

    expect(ActivityLog::query()->where('action', 'updated')->exists())->toBeFalse();
});

it('records creations by the API and deletions', function () {
    $contract = Contract::factory()->create();
    $broker = $contract->client->broker;
    $contract->update(['status' => 'cancelled']);
    Sanctum::actingAs($broker);

    $this->deleteJson("/api/v1/contracts/{$contract->id}")->assertNoContent();

    $log = ActivityLog::query()->where('action', 'deleted')->where('subject_type', 'contract')->sole();

    expect($log->user_id)->toBe($broker->id)
        ->and($log->subject_label)->toBe($contract->policy_number);
});

it('shows the journal of the brokerage to its admins only', function () {
    $client = Client::factory()->create();
    Client::factory()->create();
    $admin = User::factory()->admin()->create(['brokerage_id' => $client->brokerage_id]);

    Sanctum::actingAs($client->broker);
    $this->getJson('/api/v1/activity-logs')->assertForbidden();

    Sanctum::actingAs($admin);
    $this->getJson('/api/v1/activity-logs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.subject_id', $client->id);

    $this->getJson('/api/v1/activity-logs?filter[action]=deleted')->assertJsonCount(0, 'data');
});
