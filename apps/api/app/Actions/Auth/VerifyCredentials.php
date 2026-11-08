<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class VerifyCredentials
{
    /**
     * Check credentials without signing in, and that the account may use this application.
     *
     * @param  array{email: string, password: string}  $credentials
     * @param  list<UserRole>  $allowedRoles
     *
     * @throws ValidationException when the credentials are wrong
     * @throws AuthorizationException when the account belongs to the other application
     */
    public function handle(array $credentials, array $allowedRoles): User
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        if (! $guard->validate($credentials)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = $guard->getLastAttempted();

        if (! in_array($user->role, $allowedRoles, true)) {
            throw new AuthorizationException(__('This account cannot sign in to this application.'));
        }

        // A deleted client keeps a user row, but no longer has anything to see.
        if ($user->role === UserRole::Client && $user->clientProfile === null) {
            throw new AuthorizationException(__('This account is no longer active.'));
        }

        return $user;
    }
}
