<?php

namespace App\Actions\Portal;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use App\Notifications\PortalInvitation;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Opens the portal to a client: a pending account, and a link to choose a password.
 * Inviting again while the invitation waits sends a fresh link.
 */
class InviteClient
{
    /**
     * @return string the link the client opens to choose a password
     */
    public function handle(Client $client, User $broker): string
    {
        $account = DB::transaction(fn () => $this->pendingAccount($client));

        /** @var PasswordBroker $invitations */
        $invitations = Password::broker('invitations');
        $url = self::url($account->email, $invitations->createToken($account));

        $account->notify(new PortalInvitation($url, $broker->name, $client->brokerage->name));

        return $url;
    }

    public static function url(string $email, string $token): string
    {
        return rtrim((string) config('app.portal_url'), '/').'/invitation?'.http_build_query(['token' => $token, 'email' => $email]);
    }

    private function pendingAccount(Client $client): User
    {
        $account = $client->user;

        if ($account !== null && $account->invited_at === null) {
            throw new ConflictHttpException(__('This client already uses the portal.'));
        }

        if ($account === null) {
            if (User::query()->where('email', $client->email)->exists()) {
                throw new ConflictHttpException(__('Another account already uses this email address.'));
            }

            $account = new User([
                'name' => $client->displayName(),
                'email' => $client->email,
                // Unusable until the client chooses their own.
                'password' => Str::password(40),
            ]);
            $account->role = UserRole::Client;
            $account->brokerage_id = $client->brokerage_id;
        }

        // The invitation goes out in the language the broker is using.
        $account->forceFill(['invited_at' => now(), 'locale' => in_array(App::getLocale(), ['fr', 'nl'], true) ? App::getLocale() : $account->locale]);
        $account->save();

        $client->user()->associate($account)->save();

        return $account;
    }
}
