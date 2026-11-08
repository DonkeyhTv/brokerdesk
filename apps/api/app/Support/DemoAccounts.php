<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * In demo mode, everyone shares the accounts offered on the login pages.
 * Nobody may change or remove them, so that the next visitor can still sign in.
 */
final class DemoAccounts
{
    public static function protects(?User $user): bool
    {
        if ($user === null || ! config('demo.enabled')) {
            return false;
        }

        /** @var list<array{email: string}> $accounts */
        $accounts = config('demo.accounts');

        return in_array(Str::lower($user->email), array_map(
            fn (array $account) => Str::lower($account['email']),
            $accounts,
        ), true);
    }
}
