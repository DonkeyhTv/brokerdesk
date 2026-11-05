<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ConcealsRecords;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    use ConcealsRecords;

    public function viewAny(User $user, Contract $contract): Response
    {
        return $this->conceal($user->canSee($contract->client));
    }

    public function view(User $user, Document $document): Response
    {
        $client = $document->contract->client;

        return $this->conceal($user->manages($client) || ($user->isAccountOf($client) && $document->visible_to_client));
    }

    public function create(User $user, Contract $contract): Response
    {
        return $this->conceal($user->canSee($contract->client), $user->manages($contract->client));
    }

    public function update(User $user, Document $document): Response
    {
        $client = $document->contract->client;

        return $this->conceal($user->canSee($client), $user->manages($client));
    }

    public function delete(User $user, Document $document): Response
    {
        $client = $document->contract->client;

        return $this->conceal($user->canSee($client), $user->manages($client));
    }
}
