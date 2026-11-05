<?php

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function portalClient(): array
{
    $account = User::factory()->client()->create();
    $client = Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);

    return [$account, $client];
}

it('ends portal access when a client is deleted', function () {
    [$account, $client] = portalClient();
    $token = $account->createToken('portal')->plainTextToken;
    Contract::factory()->withStatus(ContractStatus::Cancelled)->create(['client_id' => $client->id]);
    $broker = $client->broker;

    $this->withToken($token)->getJson('/api/v1/contracts')->assertOk();

    Auth::forgetGuards();
    $this->flushHeaders();
    Sanctum::actingAs($broker);
    $this->deleteJson("/api/v1/clients/{$client->id}")->assertNoContent();

    Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/contracts')->assertUnauthorized();

    Auth::forgetGuards();
    $this->flushHeaders();
    $this->postJson('/api/v1/auth/tokens', ['email' => $account->email, 'password' => 'password'])
        ->assertForbidden();
});

it('refuses a portal sign-in for an account with no client record', function () {
    $account = User::factory()->client()->create();

    $this->postJson('/api/v1/auth/tokens', ['email' => $account->email, 'password' => 'password'])
        ->assertForbidden();
});

it('keeps document names safe to download', function (string $name, string $stored) {
    $contract = Contract::factory()->create();
    $path = "documents/{$contract->brokerage_id}/safe.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4');
    $document = Document::factory()->create(['contract_id' => $contract->id, 'path' => $path]);
    Sanctum::actingAs($contract->client->broker);

    $this->patchJson("/api/v1/documents/{$document->id}", ['original_name' => $name])
        ->assertOk()
        ->assertJsonPath('data.original_name', $stored);

    $this->get("/api/v1/documents/{$document->id}/download")->assertOk();
})->with([
    'slash' => ['a/b.pdf', 'a_b.pdf'],
    'backslash' => ['a\\b.pdf', 'a_b.pdf'],
    'line break in a header' => ["a\r\nX-Injected: 1.pdf", 'a_X-Injected: 1.pdf'],
    'accents' => ['Résumé été.pdf', 'Résumé été.pdf'],
]);

it('accepts an upload whose name has a Windows path', function () {
    $contract = Contract::factory()->create();
    Sanctum::actingAs($contract->client->broker);
    $file = UploadedFile::fake()->createWithContent('..\\evil.pdf', "%PDF-1.4\ntrailer<<>>\n%%EOF\n");

    $id = $this->post("/api/v1/contracts/{$contract->id}/documents", ['file' => $file], ['Accept' => 'application/json'])
        ->assertCreated()
        ->json('data.id');

    $this->get("/api/v1/documents/{$id}/download")->assertOk();
});
