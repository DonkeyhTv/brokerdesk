<?php

namespace App\Actions\Documents;

use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class StoreDocument
{
    public function handle(Contract $contract, User $uploader, UploadedFile $file, bool $visibleToClient): Document
    {
        $path = $file->store("documents/{$contract->brokerage_id}", 'local');

        $document = new Document;
        $document->forceFill([
            'contract_id' => $contract->id,
            'uploaded_by' => $uploader->id,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'visible_to_client' => $visibleToClient,
        ])->save();

        return $document;
    }
}
