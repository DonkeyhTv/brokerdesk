<?php

use App\Enums\ClaimStatus;
use App\Enums\ClientType;
use App\Enums\ContractStatus;
use App\Enums\UserRole;
use App\Models\Brokerage;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Support\EnterpriseNumber;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the documented demo accounts', function (string $email, UserRole $role) {
    $this->seed(DemoSeeder::class);

    $user = User::query()->where('email', $email)->firstOrFail();

    expect($user->role)->toBe($role)
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->brokerage?->name)->toBe('Cabinet Démo Meuse');
})->with([
    ['admin@example.be', UserRole::Admin],
    ['broker@example.be', UserRole::Broker],
    ['client@example.be', UserRole::Client],
    ['company@example.be', UserRole::Client],
]);

it('gives the demo client contracts and claims to look at', function () {
    $this->seed(DemoSeeder::class);

    $profile = User::query()->where('email', 'client@example.be')->firstOrFail()->clientProfile;

    expect($profile?->contracts)->toHaveCount(3)
        ->and(Claim::query()->whereIn('contract_id', $profile?->contracts->modelKeys() ?? [])->count())->toBe(2);
});

it('fills every renewal window of the dashboard for the demo brokerage', function (int $from, int $to) {
    $this->seed(DemoSeeder::class);

    $brokerage = Brokerage::query()->where('name', 'Cabinet Démo Meuse')->firstOrFail();

    $count = $brokerage->contracts()
        ->where('status', ContractStatus::Active)
        ->whereBetween('expires_on', [now()->addDays($from)->toDateString(), now()->addDays($to)->toDateString()])
        ->count();

    expect($count)->toBeGreaterThan(0);
})->with([[0, 30], [31, 60], [61, 90]]);

it('covers open claims and every contract status the demo needs', function () {
    $this->seed(DemoSeeder::class);

    expect(Claim::query()->where('status', ClaimStatus::Open)->exists())->toBeTrue()
        ->and(Contract::query()->where('status', ContractStatus::Cancelled)->exists())->toBeTrue()
        ->and(Contract::query()->where('status', ContractStatus::Expired)->exists())->toBeTrue();
});

it('only produces valid Belgian data', function () {
    $this->seed(DemoSeeder::class);

    $clients = Client::query()->get();

    $companies = $clients->where('type', ClientType::Company);

    expect($companies)->not->toBeEmpty()
        ->and($clients->where('type', ClientType::Individual))->not->toBeEmpty();

    // Reading the attribute parses the number, which validates its check digits.
    $companies->each(fn (Client $company) => expect($company->enterprise_number)->toBeInstanceOf(EnterpriseNumber::class));

    $clients->each(function (Client $client) {
        expect($client->postal_code)->toMatch('/^[1-9]\d{3}$/')
            ->and($client->email)->toEndWith('example.be');
    });
});

it('keeps both brokerages separated', function () {
    $this->seed(DemoSeeder::class);

    expect(Brokerage::query()->count())->toBe(2);

    Client::query()->with('broker')->get()->each(
        fn (Client $client) => expect($client->broker->brokerage_id)->toBe($client->brokerage_id)
    );
    Contract::query()->with('client')->get()->each(
        fn (Contract $contract) => expect($contract->client->brokerage_id)->toBe($contract->brokerage_id)
    );
});

it('loads demo data once in the local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->artisan('demo:seed')->assertSuccessful();
    $users = User::query()->count();
    expect($users)->toBeGreaterThan(0);

    $this->artisan('demo:seed')->assertSuccessful();
    expect(User::query()->count())->toBe($users);
});

it('never loads demo data outside the local environment', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('demo:seed')->assertSuccessful();

    expect(User::query()->count())->toBe(0);
});

it('refuses to run the demo seeder in production', function () {
    app()->detectEnvironment(fn () => 'production');

    (new DemoSeeder)->run();
})->throws(RuntimeException::class, 'demo mode');
