<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\Contract;
use App\Models\User;

class ClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Claim $claim): bool
    {
        $client = $claim->contract->client;

        return $user->manages($client) || $user->isAccountOf($client);
    }

    public function create(User $user, Contract $contract): bool
    {
        return $user->manages($contract->client) || $user->isAccountOf($contract->client);
    }

    public function update(User $user, Claim $claim): bool
    {
        return $user->manages($claim->contract->client);
    }

    public function delete(User $user, Claim $claim): bool
    {
        return $user->role === UserRole::Admin && $user->manages($claim->contract->client);
    }
}
