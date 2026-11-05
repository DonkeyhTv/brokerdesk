<?php

namespace App\Models;

use App\Casts\EnterpriseNumberCast;
use App\Contracts\Auditable;
use App\Enums\ClientType;
use App\Enums\UserRole;
use App\Observers\ActivityObserver;
use App\Support\EnterpriseNumber;
use Database\Factories\ClientFactory;
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
 * @property EnterpriseNumber|null $enterprise_number
 */
#[ObservedBy(ActivityObserver::class)]
#[Fillable(['type', 'first_name', 'last_name', 'company_name', 'enterprise_number', 'email', 'phone', 'street', 'postal_code', 'city'])]
class Client extends Model implements Auditable
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Brokerage, $this>
     */
    public function brokerage(): BelongsTo
    {
        return $this->belongsTo(Brokerage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function broker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    /**
     * The portal account of this client, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function displayName(): string
    {
        return $this->company_name ?? trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Match a name, an email or an enterprise number, ignoring accents and case.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $pattern = '%'.addcslashes($term, '%_\\').'%';
        $digits = preg_replace('/\D/', '', $term);

        $query->where(function (Builder $clients) use ($pattern, $digits) {
            $clients->whereRaw('search_name LIKE immutable_unaccent(lower(?))', [$pattern])
                ->orWhere('email', 'ilike', $pattern);

            if ($digits !== null && strlen($digits) >= 3) {
                $clients->orWhere('enterprise_number', 'like', '%'.$digits.'%');
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::Admin => $query->where('brokerage_id', $user->brokerage_id),
            UserRole::Broker => $query->where('broker_id', $user->id),
            UserRole::Client => $query->where('user_id', $user->id),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'enterprise_number' => EnterpriseNumberCast::class,
        ];
    }

    public function auditBrokerageId(): int
    {
        return $this->brokerage_id;
    }

    public function auditLabel(): string
    {
        return $this->displayName();
    }
}
