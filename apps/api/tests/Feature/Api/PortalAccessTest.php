<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use App\Notifications\PortalInvitation;
use App\Notifications\ResetPortalPassword;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Notification::fake());

/**
 * @return array{0: Client, 1: string} the client and the link of their invitation
 */
function invitedClient(): array
{
    $client = Client::factory()->create();
    Sanctum::actingAs($client->broker);
    test()->postJson("/api/v1/clients/{$client->id}/invitation")->assertCreated();

    $url = '';
    Notification::assertSentTo($client->fresh()?->user, PortalInvitation::class, function (PortalInvitation $notification) use (&$url) {
        $url = $notification->url;

        return true;
    });

    return [$client->fresh() ?? $client, $url];
}

/**
 * @return array{token: string, email: string}
 */
function linkParameters(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    /** @var array{token: string, email: string} $query */
    return $query;
}

it('invites a client to the portal with a pending account and a link to the portal', function () {
    [$client, $url] = invitedClient();
    $account = $client->user;

    expect($account?->role)->toBe(UserRole::Client)
        ->and($account?->email)->toBe($client->email)
        ->and($account?->brokerage_id)->toBe($client->brokerage_id)
        ->and($account?->invited_at)->not->toBeNull()
        ->and($url)->toStartWith('http://localhost:3000/invitation?')
        ->and(linkParameters($url)['email'])->toBe($client->email);

    $this->getJson("/api/v1/clients/{$client->id}")->assertJsonPath('data.portal.status', 'invited');
});

it('shows the link in the response in demo mode only, since demo mails never leave the server', function () {
    $client = Client::factory()->create();
    Sanctum::actingAs($client->broker);

    $this->postJson("/api/v1/clients/{$client->id}/invitation")->assertJsonPath('invitation_url', null);

    config(['demo.enabled' => true]);

    expect($this->postJson("/api/v1/clients/{$client->id}/invitation")->json('invitation_url'))
        ->toStartWith('http://localhost:3000/invitation?token=');
});

it('sends a fresh link while the invitation waits, and refuses once the client uses the portal', function () {
    [$client, $firstUrl] = invitedClient();

    $this->postJson("/api/v1/clients/{$client->id}/invitation")->assertCreated();
    $this->postJson('/api/v1/auth/invitation', [...linkParameters($firstUrl), 'password' => 'a-good-password', 'password_confirmation' => 'a-good-password'])
        ->assertJsonValidationErrors('token');

    $client->user?->forceFill(['invited_at' => null])->save();

    $this->postJson("/api/v1/clients/{$client->id}/invitation")->assertConflict();
});

it('refuses an address that another account already uses', function () {
    $client = Client::factory()->create();
    User::factory()->create(['email' => $client->email]);
    Sanctum::actingAs($client->broker);

    $this->postJson("/api/v1/clients/{$client->id}/invitation")->assertConflict();
});

it('lets only the brokers who manage the client invite them', function () {
    $client = Client::factory()->create();

    Sanctum::actingAs(User::factory()->create(['brokerage_id' => $client->brokerage_id]));
    $this->postJson("/api/v1/clients/{$client->id}/invitation")->assertNotFound();

    Sanctum::actingAs(User::factory()->admin()->create(['brokerage_id' => $client->brokerage_id]));
    $this->postJson("/api/v1/clients/{$client->id}/invitation")->assertCreated();
});

it('writes the invitation in the language of the inviting broker', function () {
    $client = Client::factory()->create();
    Sanctum::actingAs($client->broker);

    $this->postJson("/api/v1/clients/{$client->id}/invitation", [], ['Accept-Language' => 'nl'])->assertCreated();

    $account = $client->fresh()?->user;
    expect($account?->preferredLocale())->toBe('nl');

    Notification::assertSentTo($account, PortalInvitation::class, function (PortalInvitation $notification, array $channels, User $notifiable, ?string $locale) {
        app()->setLocale($locale ?? 'en');
        $mail = $notification->toMail($notifiable);

        return $locale === 'nl'
            && $mail->subject === 'Uw klantenomgeving bij '.$notifiable->brokerage->name
            && $mail->actionUrl === $notification->url;
    });
});

it('lets the client choose a password from the link, and signs them in', function () {
    [$client, $url] = invitedClient();

    $response = $this->postJson('/api/v1/auth/invitation', [...linkParameters($url), 'password' => 'a-good-password', 'password_confirmation' => 'a-good-password'])
        ->assertCreated()
        ->assertJsonPath('user.email', $client->email);

    $account = $client->user?->fresh();
    expect($account?->invited_at)->toBeNull()
        ->and(Hash::check('a-good-password', (string) $account?->password))->toBeTrue();

    $this->withToken($response->json('token'))->getJson('/api/v1/me')->assertOk();

    $this->postJson('/api/v1/auth/invitation', [...linkParameters($url), 'password' => 'another-password', 'password_confirmation' => 'another-password'])
        ->assertJsonValidationErrors('token');
});

it('refuses a wrong, short or expired invitation', function () {
    [, $url] = invitedClient();
    $link = linkParameters($url);

    $this->postJson('/api/v1/auth/invitation', [...$link, 'token' => 'wrong', 'password' => 'a-good-password', 'password_confirmation' => 'a-good-password'])
        ->assertJsonValidationErrors('token');
    $this->postJson('/api/v1/auth/invitation', [...$link, 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertJsonValidationErrors('password');
    $this->postJson('/api/v1/auth/invitation', [...$link, 'password' => 'a-good-password', 'password_confirmation' => 'different-password'])
        ->assertJsonValidationErrors('password');

    $this->travel(8)->days();

    $this->postJson('/api/v1/auth/invitation', [...$link, 'password' => 'a-good-password', 'password_confirmation' => 'a-good-password'])
        ->assertJsonValidationErrors('token');
});

it('answers the same to every reset request, and mails active portal clients only', function () {
    $active = User::factory()->client()->create();
    Client::factory()->create(['brokerage_id' => $active->brokerage_id, 'user_id' => $active->id]);
    $staff = User::factory()->create();
    [$invited] = invitedClient();

    foreach ([$active->email, $staff->email, (string) $invited->user?->email, 'nobody@example.be'] as $email) {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $email])
            ->assertAccepted()
            ->assertJsonPath('message', 'If an account uses this address, a link to choose a new password is on its way.');
    }

    Notification::assertSentTo($active, ResetPortalPassword::class, fn (ResetPortalPassword $notification) => str_starts_with($notification->url, 'http://localhost:3000/nouveau-mot-de-passe?token='));
    Notification::assertNotSentTo($staff, ResetPortalPassword::class);
    Notification::assertNotSentTo($invited->user, ResetPortalPassword::class);
});

it('resets the password from the link and signs out every session', function () {
    $account = User::factory()->client()->create();
    Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);
    $account->createToken('portal');

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $account->email]);
    $url = '';
    Notification::assertSentTo($account, ResetPortalPassword::class, function (ResetPortalPassword $notification) use (&$url) {
        $url = $notification->url;

        return true;
    });

    $this->postJson('/api/v1/auth/reset-password', [...linkParameters($url), 'token' => 'wrong', 'password' => 'a-new-password', 'password_confirmation' => 'a-new-password'])
        ->assertJsonValidationErrors('token');

    $this->postJson('/api/v1/auth/reset-password', [...linkParameters($url), 'password' => 'a-new-password', 'password_confirmation' => 'a-new-password'])
        ->assertNoContent();

    expect($account->tokens()->count())->toBe(0);
    $this->postJson('/api/v1/auth/tokens', ['email' => $account->email, 'password' => 'a-new-password'])->assertCreated();
});

it('never mails nor resets the shared demo accounts', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);
    $demo = User::query()->where('email', 'client@example.be')->sole();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $demo->email])->assertAccepted();
    Notification::assertNotSentTo($demo, ResetPortalPassword::class);

    $token = Password::broker()->createToken($demo);
    $this->postJson('/api/v1/auth/reset-password', ['email' => $demo->email, 'token' => $token, 'password' => 'taken-over-password', 'password_confirmation' => 'taken-over-password'])
        ->assertJsonValidationErrors('token');

    expect(Hash::check('password', $demo->fresh()?->password ?? ''))->toBeTrue();
});

it('tells the back office whether each client uses the portal', function () {
    $broker = User::factory()->create();
    $none = Client::factory()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id]);
    $account = User::factory()->client()->create(['brokerage_id' => $broker->brokerage_id]);
    $active = Client::factory()->create(['brokerage_id' => $broker->brokerage_id, 'broker_id' => $broker->id, 'user_id' => $account->id]);
    Sanctum::actingAs($broker);

    $statuses = collect($this->getJson('/api/v1/clients')->json('data'))->pluck('portal.status', 'id');

    expect($statuses[$none->id])->toBe('none')
        ->and($statuses[$active->id])->toBe('active');
});
