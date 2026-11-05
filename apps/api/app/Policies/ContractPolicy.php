<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->manages($contract->client) || $user->isAccountOf($contract->client);
    }

    public function create(User $user, Client $client): bool
    {
        return $user->manages($client);
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->manages($contract->client);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->manages($contract->client);
    }
}
