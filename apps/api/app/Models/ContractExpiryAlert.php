<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['threshold_days', 'expires_on'])]
class ContractExpiryAlert extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'threshold_days' => 'integer',
            'expires_on' => 'immutable_date',
        ];
    }
}
