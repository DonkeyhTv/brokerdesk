<?php

namespace App\Http\Resources;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Claim
 */
class ClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'occurred_on' => $this->occurred_on->toDateString(),
            'description' => $this->description,
            'status' => $this->status,
            'estimated_amount_cents' => $this->estimated_amount_cents,
            'settled_amount_cents' => $this->settled_amount_cents,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'contract' => $this->whenLoaded('contract', fn () => new ContractResource($this->contract)),
            'declared_by' => $this->whenLoaded('declarer', fn () => ['id' => $this->declarer->id, 'name' => $this->declarer->name]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
