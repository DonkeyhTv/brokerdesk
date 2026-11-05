<?php

use App\Enums\ClientType;
use App\Models\User;
use App\Notifications\ContractExpiring;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

it('hides the demo accounts unless demo mode is on', function () {
    $this->getJson('/api/v1/demo/accounts')->assertNotFound();
});

it('lists accounts that really work, for each application', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $response = $this->getJson('/api/v1/demo/accounts')->assertOk();
    $password = $response->json('password');

    foreach ($response->json('accounts') as $account) {
        $endpoint = $account['app'] === 'broker' ? '/api/v1/auth/session' : '/api/v1/auth/tokens';

        $this->postJson($endpoint, ['email' => $account['email'], 'password' => $password], ['Origin' => 'http://localhost:5173'])
            ->assertSuccessful();
    }
});

it('offers a company client account with its own contracts and claims', function () {
    $this->seed(DemoSeeder::class);

    $profile = User::query()->where('email', 'company@example.be')->firstOrFail()->clientProfile;

    expect($profile?->type)->toBe(ClientType::Company)
        ->and($profile?->contracts)->toHaveCount(3);
});

it('allows demo data in production only in demo mode', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('demo:seed')->assertSuccessful();
    expect(User::query()->count())->toBe(0);

    config(['demo.enabled' => true]);

    $this->artisan('demo:seed')->assertSuccessful();
    expect(User::query()->count())->toBeGreaterThan(0);
});

it('fills the notification bell of the demo broker', function () {
    Notification::fake();

    $this->seed(DemoSeeder::class);

    Notification::assertSentTo(User::query()->where('email', 'broker@example.be')->firstOrFail(), ContractExpiring::class);
});

it('refuses to erase data outside demo mode', function () {
    $user = User::factory()->create();

    $this->artisan('demo:reset')->assertFailed();

    expect($user->fresh())->not->toBeNull();
});

it('schedules the nightly reset only in demo mode', function () {
    $resetEvent = fn () => collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'demo:reset'));

    expect($resetEvent()->expression)->toBe('0 3 * * *')
        ->and($resetEvent()->filtersPass(app()))->toBeFalse();

    config(['demo.enabled' => true]);

    expect($resetEvent()->filtersPass(app()))->toBeTrue();
});
