<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

function fromBackOffice(): array
{
    return ['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/'];
}

it('signs staff in with a session', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => 'password'], fromBackOffice())
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', $user->role->value)
        ->assertJsonPath('data.brokerage.id', $user->brokerage_id)
        ->assertJsonMissingPath('data.password');

    $this->assertAuthenticatedAs($user, 'web');
    $this->getJson('/api/v1/me', fromBackOffice())->assertOk()->assertJsonPath('data.id', $user->id);
})->with(['admin', 'broker']);

it('keeps clients out of the back office, without creating a session', function () {
    $client = User::factory()->client()->create();

    $this->postJson('/api/v1/auth/session', ['email' => $client->email, 'password' => 'password'], fromBackOffice())
        ->assertForbidden();

    $this->assertGuest('web');
});

it('rejects a wrong password and an unknown email with the same error', function (array $credentials) {
    User::factory()->create(['email' => 'known@example.be']);

    $this->postJson('/api/v1/auth/session', $credentials, fromBackOffice())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('auth.failed')]);

    $this->assertGuest('web');
})->with([
    'wrong password' => [['email' => 'known@example.be', 'password' => 'wrong']],
    'unknown email' => [['email' => 'unknown@example.be', 'password' => 'password']],
]);

it('validates the login payload', function () {
    $this->postJson('/api/v1/auth/session', ['email' => 'not-an-email'], fromBackOffice())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('refuses session sign-in from an origin that is not first party', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => 'password'], ['Origin' => 'https://evil.example'])
        ->assertBadRequest();

    $this->assertGuest('web');
});

it('signs out and closes the session', function () {
    $user = User::factory()->create();
    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => 'password'], fromBackOffice())->assertOk();

    $this->deleteJson('/api/v1/auth/session', [], fromBackOffice())->assertNoContent();

    Auth::forgetGuards();
    $this->getJson('/api/v1/me', fromBackOffice())->assertUnauthorized();
});
