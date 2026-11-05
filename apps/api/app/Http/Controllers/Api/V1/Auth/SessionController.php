<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\VerifyCredentials;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Cookie sessions for the broker back office, a first-party SPA.
 */
class SessionController extends Controller
{
    public function store(LoginRequest $request, VerifyCredentials $verifyCredentials): UserResource
    {
        abort_unless($request->hasSession(), Response::HTTP_BAD_REQUEST, 'Session sign-in is only available to first-party front ends.');

        $user = $verifyCredentials->handle($request->credentials(), [UserRole::Admin, UserRole::Broker]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return new UserResource($user);
    }

    public function destroy(Request $request): Response
    {
        abort_unless($request->hasSession(), Response::HTTP_BAD_REQUEST, 'There is no session to close.');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
