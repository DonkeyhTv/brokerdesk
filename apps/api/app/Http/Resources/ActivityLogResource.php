<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->subject_label,
            'changes' => $this->changes,
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : ['id' => $this->user->id, 'name' => $this->user->name]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
