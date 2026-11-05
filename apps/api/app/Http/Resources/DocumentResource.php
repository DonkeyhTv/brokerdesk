<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'original_name' => $this->original_name,
            'size_bytes' => $this->size_bytes,
            'mime_type' => $this->mime_type,
            'visible_to_client' => $this->visible_to_client,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
