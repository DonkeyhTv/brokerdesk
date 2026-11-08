<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 */
class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->displayName(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company_name' => $this->company_name,
            'enterprise_number' => $this->enterprise_number?->formatted(),
            'email' => $this->email,
            'phone' => $this->phone,
            'street' => $this->street,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'broker' => $this->whenLoaded('broker', fn () => ['id' => $this->broker->id, 'name' => $this->broker->name]),
            'contracts_count' => $this->whenCounted('contracts'),
            // whenLoaded() would leave out clients without an account, the ones this is most about.
            'portal' => $this->when($this->resource->relationLoaded('user'), fn () => [
                'status' => $this->user === null ? 'none' : ($this->user->invited_at !== null ? 'invited' : 'active'),
                'invited_at' => $this->user?->invited_at?->toIso8601String(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
