<?php

use App\Models\User;

it('limits login attempts per account and address', function (string $endpoint) {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson($endpoint, ['email' => $user->email, 'password' => 'wrong'], ['Origin' => 'http://localhost:5173'])
            ->assertUnprocessable();
    }

    $this->postJson($endpoint, ['email' => $user->email, 'password' => 'wrong'], ['Origin' => 'http://localhost:5173'])
        ->assertTooManyRequests();
})->with(['/api/v1/auth/session', '/api/v1/auth/tokens']);

it('does not let another account be locked out by these attempts', function () {
    $victim = User::factory()->create();
    $other = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        $this->postJson('/api/v1/auth/tokens', ['email' => $other->email, 'password' => 'wrong']);
    }

    $this->postJson('/api/v1/auth/tokens', ['email' => $victim->email, 'password' => 'wrong'])
        ->assertUnprocessable();
});

it('limits an address trying many accounts', function () {
    foreach (range(1, 30) as $attempt) {
        $this->postJson('/api/v1/auth/tokens', ['email' => "user{$attempt}@example.be", 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/tokens', ['email' => 'another@example.be', 'password' => 'wrong'])
        ->assertTooManyRequests();
});
