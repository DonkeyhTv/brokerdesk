<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        $client = $document->contract->client;

        return $user->manages($client) || ($user->isAccountOf($client) && $document->visible_to_client);
    }

    public function create(User $user, Contract $contract): bool
    {
        return $user->manages($contract->client);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->manages($document->contract->client);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->manages($document->contract->client);
    }
}
