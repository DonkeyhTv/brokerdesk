<?php

namespace App\Http\Resources;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'policy_number' => $this->policy_number,
            'type' => $this->type,
            'status' => $this->status,
            'starts_on' => $this->starts_on->toDateString(),
            'expires_on' => $this->expires_on->toDateString(),
            'notice_deadline' => $this->noticeDeadline()->toDateString(),
            'annual_premium_cents' => $this->annual_premium_cents,
            'payment_frequency' => $this->payment_frequency,
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->displayName()]),
            'insurer' => $this->whenLoaded('insurer', fn () => ['id' => $this->insurer->id, 'name' => $this->insurer->name]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
