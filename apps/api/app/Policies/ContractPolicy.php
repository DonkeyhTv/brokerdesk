<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use App\Policies\Concerns\ConcealsRecords;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    use ConcealsRecords;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): Response
    {
        return $this->conceal($user->canSee($contract->client));
    }

    public function export(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Contract $contract): Response
    {
        return $this->conceal($user->canSee($contract->client), $user->manages($contract->client));
    }

    public function delete(User $user, Contract $contract): Response
    {
        return $this->conceal($user->canSee($contract->client), $user->manages($contract->client));
    }
}
