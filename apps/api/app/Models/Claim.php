<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Enums\ClaimStatus;
use App\Observers\ActivityObserver;
use Carbon\CarbonImmutable;
use Database\Factories\ClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ClaimStatus $status
 * @property CarbonImmutable $occurred_on
 * @property CarbonImmutable|null $closed_at
 */
#[ObservedBy(ActivityObserver::class)]
#[Fillable(['occurred_on', 'description', 'status', 'estimated_amount_cents', 'settled_amount_cents'])]
class Claim extends Model implements Auditable
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
        return $this->belongsTo(Contract::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function declarer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('contract', fn (Builder $contracts) => $contracts->visibleTo($user));
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

    public function auditBrokerageId(): int
    {
        return $this->brokerage_id;
    }

    public function auditLabel(): string
    {
        return $this->reference;
    }
}
