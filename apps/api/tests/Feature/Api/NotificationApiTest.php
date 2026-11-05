<?php

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiring;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function notifiedBroker(int $count = 2): User
{
    $contract = Contract::factory()->expiringInDays(25)->create();
    $broker = $contract->client->broker;

    foreach (range(1, $count) as $ignored) {
        $broker->notifyNow(new ContractExpiring($contract, 30), ['database']);
    }

    return $broker;
}

it('lists my notifications with their useful data', function () {
    $broker = notifiedBroker();
    notifiedBroker();
    Sanctum::actingAs($broker);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('unread_count', 2)
        ->assertJsonPath('data.0.type', 'contract_expiring')
        ->assertJsonPath('data.0.data.threshold_days', 30)
        ->assertJsonStructure(['data' => [['id', 'data' => ['contract_id', 'policy_number', 'client_name', 'expires_on', 'notice_deadline']]], 'meta']);
});

it('marks one notification as read, and filters on unread', function () {
    $broker = notifiedBroker();
    Sanctum::actingAs($broker);
    $id = $broker->notifications()->firstOrFail()->id;

    $this->postJson("/api/v1/notifications/{$id}/read")->assertOk();

    $this->getJson('/api/v1/notifications?unread=1')->assertJsonCount(1, 'data')->assertJsonPath('unread_count', 1);
});

it('marks all as read', function () {
    Sanctum::actingAs(notifiedBroker());

    $this->postJson('/api/v1/notifications/read-all')->assertNoContent();

    $this->getJson('/api/v1/notifications')->assertJsonCount(2, 'data')->assertJsonPath('unread_count', 0);
});

it('hides the notifications of someone else, and answers 404 to a malformed id', function () {
    $owner = notifiedBroker();
    $id = $owner->notifications()->firstOrFail()->id;
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/notifications/{$id}/read")->assertNotFound();
    $this->postJson('/api/v1/notifications/not-a-uuid/read')->assertNotFound();
    $this->postJson('/api/v1/notifications/'.Str::uuid().'/read')->assertNotFound();

    expect($owner->unreadNotifications()->count())->toBe(2);
});

it('delivers the alert in the database and by mail through the queue', function () {
    Mail::fake();
    $contract = Contract::factory()->expiringInDays(25)->create();

    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    expect($contract->client->broker->notifications()->count())->toBe(1);
});
