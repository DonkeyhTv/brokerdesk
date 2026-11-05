<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\PaymentFrequency;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['insurer_id', 'policy_number', 'type', 'status', 'starts_on', 'expires_on', 'annual_premium_cents', 'payment_frequency'])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Brokerage, $this>
     */
    public function brokerage(): BelongsTo
    {
        return $this->belongsTo(Brokerage::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Insurer, $this>
     */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }

    /**
     * @return HasMany<Claim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<ContractExpiryAlert, $this>
     */
    public function expiryAlerts(): HasMany
    {
        return $this->hasMany(ContractExpiryAlert::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'status' => ContractStatus::class,
            'payment_frequency' => PaymentFrequency::class,
            'starts_on' => 'immutable_date',
            'expires_on' => 'immutable_date',
            'annual_premium_cents' => 'integer',
        ];
    }
}
