<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\Concerns\ConcealsRecords;
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
        return $this->conceal($this->isManagedBy($target, $user));
    }

    public function delete(User $user, User $target): Response
    {
        return $this->conceal($this->isManagedBy($target, $user), $user->id !== $target->id);
    }

    private function isManagedBy(User $target, User $admin): bool
    {
        return $admin->role === UserRole::Admin
            && $admin->brokerage_id === $target->brokerage_id
            && $target->isStaff();
    }
}
