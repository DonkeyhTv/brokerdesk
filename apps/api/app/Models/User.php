<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserRole $role
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return BelongsTo<Brokerage, $this>
     */
    public function brokerage(): BelongsTo
    {
        return $this->belongsTo(Brokerage::class);
    }

    /**
     * The clients in this broker's portfolio.
     *
     * @return HasMany<Client, $this>
     */
    public function portfolio(): HasMany
    {
        return $this->hasMany(Client::class, 'broker_id');
    }

    /**
     * The client record linked to this portal account.
     *
     * @return HasOne<Client, $this>
     */
    public function clientProfile(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function isStaff(): bool
    {
        return $this->role !== UserRole::Client;
    }

    /**
     * Whether this user is in charge of the client: its broker, or an admin of its brokerage.
     */
    public function manages(Client $client): bool
    {
        return match ($this->role) {
            UserRole::Admin => $this->brokerage_id === $client->brokerage_id,
            UserRole::Broker => $this->id === $client->broker_id,
            UserRole::Client => false,
        };
    }

    public function isAccountOf(Client $client): bool
    {
        return $this->id === $client->user_id;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
