<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\VerifyCredentials;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;

/**
 * API tokens for the client portal, whose Next.js server keeps them away from the browser.
 */
class TokenController extends Controller
{
    public function store(LoginRequest $request, VerifyCredentials $verifyCredentials): JsonResponse
    {
        $user = $verifyCredentials->handle($request->credentials(), [UserRole::Client]);

        return response()->json([
            'token' => $user->createToken('portal')->plainTextToken,
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken|TransientToken $token */
        $token = $user->currentAccessToken();

        abort_unless($token instanceof PersonalAccessToken, Response::HTTP_BAD_REQUEST, 'There is no token to revoke.');

        $token->delete();

        return response()->noContent();
    }
}
