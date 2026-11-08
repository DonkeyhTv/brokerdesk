<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Portal\InviteClient;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortalInvitationController extends Controller
{
    /**
     * Invite a client to the portal.
     *
     * Sends a link to choose a password, valid for seven days. Inviting again while the invitation
     * waits sends a fresh link; a client who already uses the portal answers 409. In demo mode, the
     * response also carries the link, since demo mails never leave the server.
     */
    public function __invoke(Request $request, Client $client, InviteClient $invite): JsonResponse
    {
        /** @var User $broker */
        $broker = $request->user();

        $url = $invite->handle($client, $broker);

        return response()->json([
            'data' => new ClientResource($client->load('broker', 'user')),
            'invitation_url' => config('demo.enabled') ? $url : null,
        ], Response::HTTP_CREATED);
    }
}
