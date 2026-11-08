<?php

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiring;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('answers in the language the interface asks for', function (string $language, string $message) {
    $this->postJson('/api/v1/auth/tokens', ['email' => 'nope@example.be', 'password' => 'wrong'], ['Accept-Language' => $language])
        ->assertJsonPath('errors.email.0', $message);
})->with([
    'French' => ['fr-BE,fr;q=0.9', 'Ces identifiants ne correspondent à aucun compte.'],
    'Dutch' => ['nl-BE', 'Deze gegevens komen met geen enkel account overeen.'],
    'English by default' => ['de-DE', 'These credentials do not match our records.'],
]);

it('names the fields in that language too', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/clients', [], ['Accept-Language' => 'nl'])
        ->assertJsonPath('errors.email.0', 'Het veld e-mailadres is verplicht.');

    $this->postJson('/api/v1/clients', [], ['Accept-Language' => 'fr'])
        ->assertJsonPath('errors.email.0', 'Le champ adresse e-mail est obligatoire.');
});

it('translates the messages written for this API', function () {
    $contract = Contract::factory()->expiringInDays(200)->create();
    Sanctum::actingAs($contract->client->broker);

    $this->postJson("/api/v1/contracts/{$contract->id}/renewal", [], ['Accept-Language' => 'nl'])
        ->assertConflict()
        ->assertJsonPath('message', 'Alleen actieve of vervallen contracten worden verlengd, vanaf 90 dagen voor de vervaldag.');
});

it('remembers the language of signed-in people for their mails', function () {
    Notification::fake();
    $contract = Contract::factory()->expiringInDays(20)->create();
    $broker = $contract->client->broker;
    Sanctum::actingAs($broker);

    expect($broker->preferredLocale())->toBe('fr');

    $this->getJson('/api/v1/me', ['Accept-Language' => 'nl'])->assertOk();
    expect($broker->fresh()?->locale)->toBe('nl');

    $broker->fresh()?->notify(new ContractExpiring($contract, 30));

    Notification::assertSentTo($broker, ContractExpiring::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'nl');
});

it('keeps the remembered language when an API client asks for nothing', function () {
    $user = User::factory()->create(['locale' => 'nl']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')->assertOk();
    $this->getJson('/api/v1/me', ['Accept-Language' => 'en'])->assertOk();

    expect($user->fresh()?->locale)->toBe('nl');
});

it('writes the expiry mail in Dutch for a Dutch-speaking broker', function () {
    $this->travelTo('2026-01-01');
    $contract = Contract::factory()->create(['starts_on' => '2026-01-01', 'expires_on' => '2027-01-01']);
    app()->setLocale('nl');

    $mail = (new ContractExpiring($contract, 90))->toMail($contract->client->broker);

    expect($mail->subject)->toStartWith('Vervaldag binnen 90 dagen')
        ->and(implode(' ', $mail->introLines))->toContain('kan nog worden opgezegd tot 01/10/2026');
});

it('offers the demo accounts in the language of the login page', function () {
    config(['demo.enabled' => true]);

    $this->getJson('/api/v1/demo/accounts', ['Accept-Language' => 'nl'])
        ->assertJsonPath('accounts.0.label', 'Kantoorbeheerder')
        ->assertJsonPath('accounts.3.label', 'Zakelijke klant');

    $this->getJson('/api/v1/demo/accounts', ['Accept-Language' => 'fr'])
        ->assertJsonPath('accounts.1.label', 'Courtier');
});
