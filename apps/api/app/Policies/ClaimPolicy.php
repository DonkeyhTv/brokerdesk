<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\User;
use App\Policies\Concerns\ConcealsRecords;
use Illuminate\Auth\Access\Response;

class ClaimPolicy
{
    use ConcealsRecords;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Claim $claim): Response
    {
        return $this->conceal($user->canSee($claim->contract->client));
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Claim $claim): Response
    {
        $client = $claim->contract->client;

        return $this->conceal($user->canSee($client), $user->manages($client));
    }

    public function delete(User $user, Claim $claim): Response
    {
        $client = $claim->contract->client;

        return $this->conceal($user->canSee($client), $user->role === UserRole::Admin && $user->manages($client));
    }
}
