<?php

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractExpiryAlert;
use App\Notifications\ContractExpiring;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('alerts the broker with the tightest threshold reached', function (int $days, int $threshold) {
    $contract = Contract::factory()->expiringInDays($days)->create();

    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    Notification::assertSentTo(
        $contract->client->broker,
        ContractExpiring::class,
        fn (ContractExpiring $notification) => $notification->thresholdDays === $threshold && $notification->contract->is($contract),
    );
})->with([[0, 30], [30, 30], [31, 60], [60, 60], [61, 90], [90, 90]]);

it('stays silent outside the window and for contracts that will not renew', function () {
    Contract::factory()->expiringInDays(91)->create();
    Contract::factory()->expiringInDays(-1)->create();
    Contract::factory()->expiringInDays(20)->withStatus(ContractStatus::Cancelled)->create();

    $deletedClient = Client::factory()->create();
    Contract::factory()->expiringInDays(20)->create(['client_id' => $deletedClient->id]);
    $deletedClient->delete();

    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    Notification::assertNothingSent();
    expect(ContractExpiryAlert::query()->count())->toBe(0);
});

it('never sends the same alert twice', function () {
    $contract = Contract::factory()->expiringInDays(25)->create();

    $this->artisan('contracts:notify-expiring')->assertSuccessful();
    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    Notification::assertSentToTimes($contract->client->broker, ContractExpiring::class, 1);
});

it('alerts again when the next threshold is crossed', function () {
    $contract = Contract::factory()->expiringInDays(65)->create();
    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    $this->travel(10)->days();
    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    $this->travel(30)->days();
    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    Notification::assertSentToTimes($contract->client->broker, ContractExpiring::class, 3);
    expect($contract->expiryAlerts()->pluck('threshold_days')->all())->toEqualCanonicalizing([90, 60, 30]);
});

it('starts again after a renewal', function () {
    $contract = Contract::factory()->expiringInDays(25)->create();
    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    $contract->update(['expires_on' => now()->addDays(29)->toDateString()]);
    $this->artisan('contracts:notify-expiring')->assertSuccessful();

    Notification::assertSentToTimes($contract->client->broker, ContractExpiring::class, 2);
});

it('runs every day', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'contracts:notify-expiring'));

    expect($event?->expression)->toBe('0 7 * * *');
});

it('writes a mail that says whether the contract can still be cancelled', function () {
    $this->travelTo('2026-01-01');
    $contract = Contract::factory()->create(['starts_on' => '2026-01-01', 'expires_on' => '2027-01-01']);
    $broker = $contract->client->broker;
    app()->setLocale($broker->preferredLocale());

    $mail = (new ContractExpiring($contract, 90))->toMail($broker);

    expect($mail->subject)->toContain($contract->policy_number)
        ->and(implode(' ', $mail->introLines))->toContain('01/01/2027')->toContain('résilié jusqu’au 01/10/2026');

    $this->travelTo('2026-12-01');

    expect(implode(' ', (new ContractExpiring($contract, 30))->toMail($broker)->introLines))->toContain('reconduit tacitement');
});
