<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Enums\UserRole;
use App\Observers\ActivityObserver;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ActivityObserver::class)]
#[Fillable(['original_name', 'visible_to_client'])]
class Document extends Model implements Auditable
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('contract', fn (Builder $contracts) => $contracts->visibleTo($user))
            ->when($user->role === UserRole::Client, fn (Builder $documents) => $documents->where('visible_to_client', true));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'visible_to_client' => 'boolean',
        ];
    }

    public function auditBrokerageId(): int
    {
        return $this->contract->brokerage_id;
    }

    public function auditLabel(): string
    {
        return $this->original_name;
    }
}
