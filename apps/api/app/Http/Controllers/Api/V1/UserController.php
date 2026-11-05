<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Claim;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UserController extends Controller
{
    /**
     * List the staff of the brokerage.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $admin */
        $admin = $request->user();

        $staff = $admin->brokerage->users()->whereIn('role', ['admin', 'broker']);

        $users = QueryBuilder::for($staff)
            ->allowedFilters(AllowedFilter::exact('role'))
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('name')
            ->with('brokerage', 'clientProfile')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return UserResource::collection($users);
    }

    /**
     * Add a staff member to the brokerage.
     */
    public function store(StoreUserRequest $request): UserResource
    {
        /** @var User $admin */
        $admin = $request->user();

        $user = new User($request->safe()->except('role'));
        $user->role = $request->enum('role', UserRole::class) ?? UserRole::Broker;
        $user->brokerage_id = $admin->brokerage_id;
        $user->save();

        return new UserResource($user);
    }

    /**
     * Show a staff member.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update a staff member.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->fill($request->safe()->only('name', 'email'));
        $user->role = $request->enum('role', UserRole::class) ?? $user->role;

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        return new UserResource($user);
    }

    /**
     * Remove a staff member.
     *
     * Refused with a 409 while they still own clients or their name is on records.
     */
    public function destroy(User $user): Response
    {
        $isReferenced = $user->portfolio()->withTrashed()->exists()
            || Claim::query()->where('declared_by', $user->id)->exists()
            || Document::query()->where('uploaded_by', $user->id)->exists();

        if ($isReferenced) {
            throw new ConflictHttpException('Reassign this person\'s clients and records first.');
        }

        $user->delete();

        return response()->noContent();
    }
}
