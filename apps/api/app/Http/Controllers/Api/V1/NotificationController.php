<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * List my notifications.
     */
    #[QueryParameter('unread', 'Only the unread notifications.', type: 'bool')]
    #[QueryParameter('per_page', 'Between 1 and 100.', type: 'int', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = ($request->boolean('unread') ? $user->unreadNotifications() : $user->notifications())
            ->paginate($this->perPage($request))
            ->withQueryString();

        return NotificationResource::collection($notifications)
            ->additional(['unread_count' => $user->unreadNotifications()->count()]);
    }

    /**
     * Mark one of my notifications as read.
     */
    public function read(Request $request, string $notification): NotificationResource
    {
        /** @var User $user */
        $user = $request->user();

        $found = $user->notifications()->findOrFail($notification);
        $found->markAsRead();

        return new NotificationResource($found);
    }

    /**
     * Mark all my notifications as read.
     */
    public function readAll(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
