<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Database\Factories\ClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['occurred_on', 'description', 'status', 'estimated_amount_cents', 'settled_amount_cents'])]
class Claim extends Model
{
    /** @use HasFactory<ClaimFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Brokerage, $this>
     */
    public function brokerage(): BelongsTo
    {
        return $this->belongsTo(Brokerage::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function declarer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'occurred_on' => 'immutable_date',
            'estimated_amount_cents' => 'integer',
            'settled_amount_cents' => 'integer',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
