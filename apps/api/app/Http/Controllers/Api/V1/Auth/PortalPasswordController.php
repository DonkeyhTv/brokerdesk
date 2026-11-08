<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChoosePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Passwords that portal clients choose themselves, from a link sent by mail. Staff passwords
 * are set by the brokerage admin instead.
 */
class PortalPasswordController extends Controller
{
    /**
     * Accept a portal invitation.
     *
     * Sets the first password and answers with a portal token, like a sign-in.
     */
    public function acceptInvitation(ChoosePasswordRequest $request): JsonResponse
    {
        /** @var PasswordBroker $invitations */
        $invitations = Password::broker('invitations');
        $user = $this->portalAccount($request->string('email')->toString());

        if ($user === null || $user->invited_at === null || ! $invitations->tokenExists($user, $request->string('token')->toString())) {
            throw ValidationException::withMessages(['token' => __('This link is no longer valid. Ask your broker for a new invitation.')]);
        }

        $user->forceFill(['password' => $request->string('password')->toString(), 'invited_at' => null])->save();
        $invitations->deleteToken($user);

        return response()->json([
            'token' => $user->createToken('portal')->plainTextToken,
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Ask for a password reset link.
     *
     * Always answers 202, so that nobody can learn which addresses have an account.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = $this->portalAccount($request->string('email')->toString());

        if ($user !== null && $user->invited_at === null && ! DemoAccounts::protects($user)) {
            /** @var PasswordBroker $resets */
            $resets = Password::broker();
            // Sends at most one link per minute and address; see config/auth.php.
            $resets->sendResetLink(['email' => $user->email]);
        }

        return response()->json(['message' => __('If an account uses this address, a link to choose a new password is on its way.')], Response::HTTP_ACCEPTED);
    }

    /**
     * Choose a new password from a reset link.
     *
     * Signs out every portal session of the account.
     */
    public function reset(ChoosePasswordRequest $request): Response
    {
        $user = $this->portalAccount($request->string('email')->toString());

        $status = $user === null || DemoAccounts::protects($user)
            ? Password::INVALID_USER
            : Password::broker()->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill(['password' => $password])->save();
                    $user->tokens()->delete();
                },
            );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => __('This link is no longer valid. Ask for a new one.')]);
        }

        return response()->noContent();
    }

    private function portalAccount(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('role', UserRole::Client)
            ->whereHas('clientProfile')
            ->first();
    }
}
