<?php

use App\Actions\Contracts\NotifyExpiringContracts;
use App\Enums\ContractStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

function renewableContract(int $daysLeft = 20, ContractStatus $status = ContractStatus::Active): Contract
{
    $contract = Contract::factory()->expiringInDays($daysLeft)->withStatus($status)->create();
    Sanctum::actingAs($contract->client->broker);

    return $contract;
}

it('renews for one year from the current expiry, keeping the start date', function () {
    $contract = renewableContract(20);
    $expiresOn = $contract->expires_on;

    $this->postJson("/api/v1/contracts/{$contract->id}/renewal")
        ->assertOk()
        ->assertJsonPath('data.starts_on', $contract->starts_on->toDateString())
        ->assertJsonPath('data.expires_on', $expiresOn->addYearNoOverflow()->toDateString())
        ->assertJsonPath('data.notice_deadline', $expiresOn->addYearNoOverflow()->subMonthsNoOverflow(3)->toDateString())
        ->assertJsonPath('data.is_renewable', false);
});

it('brings a lapsed contract back to active', function () {
    $contract = renewableContract(-10, ContractStatus::Expired);

    $this->postJson("/api/v1/contracts/{$contract->id}/renewal")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('takes the indexed premium of the new term, when given', function () {
    $contract = renewableContract();

    $this->postJson("/api/v1/contracts/{$contract->id}/renewal", ['annual_premium_cents' => 91234])
        ->assertJsonPath('data.annual_premium_cents', 91234);

    $this->postJson("/api/v1/contracts/{$contract->id}/renewal", ['annual_premium_cents' => -1])
        ->assertJsonValidationErrors('annual_premium_cents');
});

it('refuses contracts outside the renewal window, or no longer running', function (int $daysLeft, ContractStatus $status) {
    $contract = renewableContract($daysLeft, $status);

    $this->getJson("/api/v1/contracts/{$contract->id}")->assertJsonPath('data.is_renewable', false);
    $this->postJson("/api/v1/contracts/{$contract->id}/renewal")->assertConflict();

    expect($contract->fresh()?->expires_on->toDateString())->toBe($contract->expires_on->toDateString());
})->with([
    'too early' => [120, ContractStatus::Active],
    'cancelled' => [20, ContractStatus::Cancelled],
    'suspended' => [20, ContractStatus::Suspended],
    'lapsed over a year ago' => [-400, ContractStatus::Expired],
]);

it('opens renewal on the day of the first expiry alert', function () {
    expect(renewableContract(90)->isRenewable())->toBeTrue()
        ->and(renewableContract(91)->isRenewable())->toBeFalse();
});

it('lets only the brokers who manage the client renew', function () {
    $contract = Contract::factory()->expiringInDays(20)->create();

    Sanctum::actingAs(User::factory()->create(['brokerage_id' => $contract->brokerage_id]));
    $this->postJson("/api/v1/contracts/{$contract->id}/renewal")->assertNotFound();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/contracts/{$contract->id}/renewal")->assertNotFound();

    $account = User::factory()->client()->create();
    $client = Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);
    $own = Contract::factory()->for($client)->expiringInDays(20)->create(['brokerage_id' => $client->brokerage_id]);
    Sanctum::actingAs($account);
    $this->postJson("/api/v1/contracts/{$own->id}/renewal")->assertForbidden();
});

it('records the renewal in the activity log and restarts the expiry alerts', function () {
    Notification::fake();
    $contract = renewableContract(20);
    app(NotifyExpiringContracts::class)->handle();

    $this->postJson("/api/v1/contracts/{$contract->id}/renewal")->assertOk();

    $log = ActivityLog::query()->where('subject_id', $contract->id)->where('action', 'updated')->sole();
    expect($log->changes)->toHaveKey('expires_on');

    $this->travelTo($contract->fresh()?->expires_on->subDays(25));
    expect(app(NotifyExpiringContracts::class)->handle())->toBe(1);
});
