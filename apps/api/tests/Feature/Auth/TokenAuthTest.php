<?php

use App\Models\Brokerage;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

it('issues a token to a client and exposes the linked client record', function () {
    $brokerage = Brokerage::factory()->create();
    $account = User::factory()->client()->for($brokerage)->create();
    $client = Client::factory()->create(['brokerage_id' => $brokerage->id, 'user_id' => $account->id]);

    $response = $this->postJson('/api/v1/auth/tokens', ['email' => $account->email, 'password' => 'password'])
        ->assertCreated()
        ->assertJsonPath('user.client_id', $client->id);

    $this->withToken($response->json('token'))
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $account->id);
});

it('does not issue tokens to staff', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->postJson('/api/v1/auth/tokens', ['email' => $user->email, 'password' => 'password'])
        ->assertForbidden();

    expect(PersonalAccessToken::query()->count())->toBe(0);
})->with(['admin', 'broker']);

it('rejects wrong credentials', function () {
    $account = User::factory()->client()->create();

    $this->postJson('/api/v1/auth/tokens', ['email' => $account->email, 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('revokes the current token only', function () {
    $account = User::factory()->client()->create();
    $current = $account->createToken('portal')->plainTextToken;
    $other = $account->createToken('portal')->plainTextToken;

    $this->withToken($current)->deleteJson('/api/v1/auth/tokens/current')->assertNoContent();

    Auth::forgetGuards();
    $this->withToken($current)->getJson('/api/v1/me')->assertUnauthorized();

    Auth::forgetGuards();
    $this->withToken($other)->getJson('/api/v1/me')->assertOk();
});

it('refuses to revoke a token for a user signed in with a session', function () {
    $user = User::factory()->create();
    $origin = ['Origin' => 'http://localhost:5173'];

    $this->postJson('/api/v1/auth/session', ['email' => $user->email, 'password' => 'password'], $origin)->assertOk();

    $this->deleteJson('/api/v1/auth/tokens/current', [], $origin)->assertBadRequest();
});

it('expires tokens after a week', function () {
    $token = User::factory()->client()->create()->createToken('portal')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/me')->assertOk();

    $this->travel(8)->days();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
});

it('answers 401 in JSON without credentials, whatever the Accept header', function () {
    $this->get('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['message']);
    $this->getJson('/api/v1/me')->assertUnauthorized();
});
