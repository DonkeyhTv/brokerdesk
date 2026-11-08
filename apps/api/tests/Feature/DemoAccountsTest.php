<?php

use App\Enums\ContractStatus;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);
    $this->admin = User::query()->where('email', 'admin@example.be')->sole();
    Sanctum::actingAs($this->admin);
});

it('refuses to change a demo account, including your own', function (string $email) {
    $target = User::query()->where('email', $email)->sole();

    $this->putJson("/api/v1/users/{$target->id}", [
        'name' => 'Taken over',
        'email' => 'someone-else@example.be',
        'password' => 'a-brand-new-password',
        'role' => $target->role->value,
    ])->assertForbidden()->assertJsonPath('message', 'Demo accounts cannot be changed, so that every visitor can sign in.');

    $target->refresh();

    expect($target->email)->toBe($email)
        ->and(Hash::check('password', $target->password))->toBeTrue();
})->with(['admin@example.be', 'broker@example.be']);

it('refuses to remove a demo account', function () {
    $broker = User::query()->where('email', 'broker@example.be')->sole();

    $this->deleteJson("/api/v1/users/{$broker->id}")->assertForbidden();

    expect($broker->fresh())->not->toBeNull();
});

it('refuses to delete a client who signs in with a demo account, even without active contracts', function (string $email) {
    $client = User::query()->where('email', $email)->sole()->clientProfile;
    $client?->contracts()->update(['status' => ContractStatus::Cancelled]);

    $this->deleteJson("/api/v1/clients/{$client?->id}")->assertForbidden();

    expect($client?->fresh())->not->toBeNull();
})->with(['client@example.be', 'company@example.be']);

it('flags demo accounts so the interface can say why they are locked', function () {
    $this->getJson('/api/v1/me')->assertJsonPath('data.is_demo_account', true);

    $newcomer = User::factory()->create(['brokerage_id' => $this->admin->brokerage_id]);

    $this->getJson("/api/v1/users/{$newcomer->id}")->assertJsonPath('data.is_demo_account', false);
});

it('still lets visitors manage the staff they add', function () {
    $newcomer = User::factory()->create(['brokerage_id' => $this->admin->brokerage_id]);

    $this->putJson("/api/v1/users/{$newcomer->id}", ['name' => 'Renamed', 'email' => $newcomer->email, 'role' => 'broker'])->assertOk();
    $this->deleteJson("/api/v1/users/{$newcomer->id}")->assertNoContent();
});

it('protects nothing outside demo mode', function () {
    config(['demo.enabled' => false]);
    $broker = User::query()->where('email', 'broker@example.be')->sole();

    $this->putJson("/api/v1/users/{$broker->id}", ['name' => 'Renamed', 'email' => $broker->email, 'role' => 'broker'])->assertOk();
    $this->getJson('/api/v1/me')->assertJsonPath('data.is_demo_account', false);
});
