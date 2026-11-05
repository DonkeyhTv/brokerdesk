<?php

use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function pdfUpload(string $name = 'conditions.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer<<>>\n%%EOF\n");
}

function documentWorld(): object
{
    $account = User::factory()->client()->create();
    $client = Client::factory()->create(['brokerage_id' => $account->brokerage_id, 'user_id' => $account->id]);

    return (object) [
        'account' => $account,
        'broker' => $client->broker,
        'contract' => Contract::factory()->create(['client_id' => $client->id]),
    ];
}

function storedDocument(Contract $contract, array $attributes = []): Document
{
    $path = 'documents/'.$contract->brokerage_id.'/stored.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 stored');

    return Document::factory()->create(['contract_id' => $contract->id, 'path' => $path, ...$attributes]);
}

it('stores a PDF privately and hides it from the client by default', function () {
    $world = documentWorld();
    Sanctum::actingAs($world->broker);

    $response = $this->post("/api/v1/contracts/{$world->contract->id}/documents", ['file' => pdfUpload()], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.original_name', 'conditions.pdf')
        ->assertJsonPath('data.visible_to_client', false)
        ->assertJsonMissingPath('data.path');

    $document = Document::query()->findOrFail($response->json('data.id'));

    Storage::disk('local')->assertExists($document->path);
    expect($document->path)->toStartWith("documents/{$world->contract->brokerage_id}/")
        ->and($document->uploaded_by)->toBe($world->broker->id);
});

it('refuses anything but a PDF, whatever its extension', function (UploadedFile $file) {
    $world = documentWorld();
    Sanctum::actingAs($world->broker);

    $this->post("/api/v1/contracts/{$world->contract->id}/documents", ['file' => $file], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
})->with([
    'wrong extension' => fn () => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
    // A real file: test doubles report a type from their name instead of reading their content.
    'php script named .pdf' => function () {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, '<?php echo "not a pdf";');

        return new UploadedFile($path, 'fake.pdf', null, null, true);
    },
    'too large' => fn () => UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf'),
]);

it('keeps the file name from reaching outside the document', function () {
    $world = documentWorld();
    Sanctum::actingAs($world->broker);

    $this->post("/api/v1/contracts/{$world->contract->id}/documents", ['file' => pdfUpload('../../evil.pdf')], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.original_name', 'evil.pdf');
});

it('lets only the people in charge upload', function () {
    $world = documentWorld();
    $url = "/api/v1/contracts/{$world->contract->id}/documents";

    Sanctum::actingAs($world->account);
    $this->post($url, ['file' => pdfUpload()], ['Accept' => 'application/json'])->assertForbidden();

    Sanctum::actingAs(User::factory()->create());
    $this->post($url, ['file' => pdfUpload()], ['Accept' => 'application/json'])->assertNotFound();

    expect(Document::query()->count())->toBe(0);
});

it('lists and serves only the documents a client may see', function () {
    $world = documentWorld();
    $hidden = storedDocument($world->contract);
    $visible = Document::factory()->create(['contract_id' => $world->contract->id, 'visible_to_client' => true, 'path' => $hidden->path.'.visible']);
    Storage::disk('local')->put($visible->path, '%PDF-1.4 visible');
    Sanctum::actingAs($world->account);

    $this->getJson("/api/v1/contracts/{$world->contract->id}/documents")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visible->id);

    $this->get("/api/v1/documents/{$visible->id}/download")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload($visible->original_name);

    $this->getJson("/api/v1/documents/{$hidden->id}/download")->assertNotFound();
});

it('hides documents from other portfolios', function () {
    $world = documentWorld();
    $document = storedDocument($world->contract, ['visible_to_client' => true]);
    Sanctum::actingAs(User::factory()->client()->create());

    $this->getJson("/api/v1/contracts/{$world->contract->id}/documents")->assertNotFound();
    $this->getJson("/api/v1/documents/{$document->id}/download")->assertNotFound();
});

it('changes visibility and deletes the file with the record', function () {
    $world = documentWorld();
    $document = storedDocument($world->contract);
    Sanctum::actingAs($world->broker);

    $this->patchJson("/api/v1/documents/{$document->id}", ['visible_to_client' => true])
        ->assertOk()
        ->assertJsonPath('data.visible_to_client', true);

    $this->deleteJson("/api/v1/documents/{$document->id}")->assertNoContent();

    Storage::disk('local')->assertMissing($document->path);
    $this->assertModelMissing($document);
});

it('does not let a client change or delete a visible document', function () {
    $world = documentWorld();
    $document = storedDocument($world->contract, ['visible_to_client' => true]);
    Sanctum::actingAs($world->account);

    $this->patchJson("/api/v1/documents/{$document->id}", ['visible_to_client' => false])->assertForbidden();
    $this->deleteJson("/api/v1/documents/{$document->id}")->assertForbidden();
});
