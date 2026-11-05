<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('reserves staff management to admins', function (string $state) {
    Sanctum::actingAs(User::factory()->{$state}()->create());

    $this->getJson('/api/v1/users')->assertForbidden();
    $this->postJson('/api/v1/users', [])->assertForbidden();
})->with(['broker', 'client']);

it('lists the staff of the brokerage only', function () {
    $admin = User::factory()->admin()->create();
    $colleague = User::factory()->create(['brokerage_id' => $admin->brokerage_id]);
    User::factory()->client()->create(['brokerage_id' => $admin->brokerage_id]);
    User::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users')->assertOk()->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('id'))->toContain($admin->id, $colleague->id);
});

it('creates a broker in the brokerage of the admin', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/users', ['name' => 'Nora Claes', 'email' => 'nora@example.be', 'password' => 'long-enough-password', 'role' => 'broker', 'brokerage_id' => 999])
        ->assertCreated()
        ->assertJsonPath('data.role', 'broker')
        ->assertJsonMissingPath('data.password');

    $user = User::query()->where('email', 'nora@example.be')->sole();

    expect($user->brokerage_id)->toBe($admin->brokerage_id)
        ->and(Hash::check('long-enough-password', $user->password))->toBeTrue();
});

it('validates new staff', function (array $overrides, string $field) {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/users', ['name' => 'Nora', 'email' => 'nora@example.be', 'password' => 'long-enough-password', 'role' => 'broker', ...$overrides])
        ->assertJsonValidationErrors($field);
})->with([
    'short password' => [['password' => 'short'], 'password'],
    'client role' => [['role' => 'client'], 'role'],
    'bad email' => [['email' => 'nope'], 'email'],
]);

it('updates staff, keeping the password unless a new one is given', function () {
    $admin = User::factory()->admin()->create();
    $broker = User::factory()->create(['brokerage_id' => $admin->brokerage_id]);
    $hash = $broker->password;
    Sanctum::actingAs($admin);
    $payload = ['name' => 'Renamed', 'email' => $broker->email, 'role' => 'admin'];

    $this->putJson("/api/v1/users/{$broker->id}", $payload)->assertOk()->assertJsonPath('data.role', 'admin');
    expect($broker->fresh()?->password)->toBe($hash);

    $this->putJson("/api/v1/users/{$broker->id}", [...$payload, 'password' => 'another-long-password'])->assertOk();
    expect(Hash::check('another-long-password', (string) $broker->fresh()?->password))->toBeTrue();
});

it('hides people of other brokerages and portal accounts', function () {
    $admin = User::factory()->admin()->create();
    $portal = User::factory()->client()->create(['brokerage_id' => $admin->brokerage_id]);
    $stranger = User::factory()->create();
    Sanctum::actingAs($admin);

    foreach ([$portal, $stranger] as $target) {
        $this->getJson("/api/v1/users/{$target->id}")->assertNotFound();
        $this->putJson("/api/v1/users/{$target->id}", ['name' => 'x', 'email' => 'x@example.be', 'role' => 'admin'])->assertNotFound();
        $this->deleteJson("/api/v1/users/{$target->id}")->assertNotFound();
    }
});

it('does not let admins lock themselves out', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/users/{$admin->id}")->assertForbidden();
    $this->putJson("/api/v1/users/{$admin->id}", ['name' => 'x', 'email' => $admin->email, 'role' => 'broker'])
        ->assertJsonValidationErrors('role');

    expect($admin->fresh()?->role)->toBe(UserRole::Admin);
});

it('refuses to remove a broker who still has clients', function () {
    $admin = User::factory()->admin()->create();
    $broker = User::factory()->create(['brokerage_id' => $admin->brokerage_id]);
    $client = Client::factory()->create(['brokerage_id' => $admin->brokerage_id, 'broker_id' => $broker->id]);
    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/users/{$broker->id}")->assertConflict();

    $client->delete();
    $this->deleteJson("/api/v1/users/{$broker->id}")->assertConflict();

    $client->forceDelete();
    $this->deleteJson("/api/v1/users/{$broker->id}")->assertNoContent();
});
