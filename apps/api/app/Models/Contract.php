<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\PaymentFrequency;
use App\Observers\ActivityObserver;
use Carbon\CarbonImmutable;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContractType $type
 * @property ContractStatus $status
 * @property PaymentFrequency $payment_frequency
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $expires_on
 */
#[ObservedBy(ActivityObserver::class)]
#[Fillable(['insurer_id', 'policy_number', 'type', 'status', 'starts_on', 'expires_on', 'annual_premium_cents', 'payment_frequency'])]
class Contract extends Model implements Auditable
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
     * Includes soft-deleted clients, so a contract keeps its history and its access rules.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
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
     * Belgian law: a tacitly renewed contract must be cancelled three months before it expires.
     */
    public function noticeDeadline(): CarbonImmutable
    {
        return $this->expires_on->subMonthsNoOverflow(3);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('client', fn (Builder $clients) => $clients->visibleTo($user));
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

    public function auditBrokerageId(): int
    {
        return $this->brokerage_id;
    }

    public function auditLabel(): string
    {
        return $this->policy_number;
    }
}
