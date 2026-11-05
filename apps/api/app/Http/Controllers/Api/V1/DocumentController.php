<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\StoreDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * List the documents of a contract.
     *
     * Clients only see the documents marked as visible.
     */
    public function index(Request $request, Contract $contract): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return DocumentResource::collection(
            $contract->documents()->visibleTo($user)->latest()->get()
        );
    }

    /**
     * Upload a PDF to a contract.
     */
    public function store(StoreDocumentRequest $request, Contract $contract, StoreDocument $storeDocument): DocumentResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return new DocumentResource($storeDocument->handle($contract, $user, $file, $request->boolean('visible_to_client')));
    }

    /**
     * Download a document.
     */
    public function download(Document $document): StreamedResponse
    {
        return Storage::disk('local')->download($document->path, $document->original_name, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Rename a document or change its visibility.
     */
    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $document->update($request->validated());

        return new DocumentResource($document);
    }

    /**
     * Delete a document and its file.
     */
    public function destroy(Document $document): Response
    {
        $document->delete();
        Storage::disk('local')->delete($document->path);

        return response()->noContent();
    }
}
