<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\Concerns\ConcealsRecords;
use App\Support\DemoAccounts;
use Illuminate\Auth\Access\Response;

/**
 * Admins manage the staff of their own brokerage. Portal accounts are out of scope.
 */
class UserPolicy
{
    use ConcealsRecords;

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, User $target): Response
    {
        return $this->conceal($this->isManagedBy($target, $user));
    }

    public function update(User $user, User $target): Response
    {
        return $this->unlessDemoAccount($target, $this->conceal($this->isManagedBy($target, $user)));
    }

    public function delete(User $user, User $target): Response
    {
        return $this->unlessDemoAccount($target, $this->conceal($this->isManagedBy($target, $user), $user->id !== $target->id));
    }

    private function unlessDemoAccount(User $target, Response $response): Response
    {
        if ($response->allowed() && DemoAccounts::protects($target)) {
            return Response::deny('Demo accounts cannot be changed, so that every visitor can sign in.');
        }

        return $response;
    }

    private function isManagedBy(User $target, User $admin): bool
    {
        return $admin->role === UserRole::Admin
            && $admin->brokerage_id === $target->brokerage_id
            && $target->isStaff();
    }
}
