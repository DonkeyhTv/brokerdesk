<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\ConcealsRecords;
use App\Support\DemoAccounts;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    use ConcealsRecords;

    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Client $client): Response
    {
        return $this->conceal($user->canSee($client));
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Client $client): Response
    {
        return $this->conceal($user->canSee($client), $user->manages($client));
    }

    public function delete(User $user, Client $client): Response
    {
        $response = $this->conceal($user->canSee($client), $user->manages($client));

        if ($response->allowed() && DemoAccounts::protects($client->user)) {
            return Response::deny(__('This client signs in with a demo account, so that every visitor can use the portal.'));
        }

        return $response;
    }
}
