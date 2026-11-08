<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ClientController extends Controller
{
    /**
     * List clients.
     */
    #[QueryParameter('filter[search]', 'Name, email or enterprise number; ignores accents and case.', type: 'string')]
    #[QueryParameter('filter[type]', 'individual or company.', type: 'string')]
    #[QueryParameter('filter[broker_id]', 'Only the clients of this broker.', type: 'int')]
    #[QueryParameter('sort', 'name, city or created_at; prefix with - to reverse.', type: 'string')]
    #[QueryParameter('per_page', 'Between 1 and 100.', type: 'int', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $clients = QueryBuilder::for(Client::query()->visibleTo($user))
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, mixed $value) {
                    /** @var Builder<Client> $query */
                    $query->search((string) $value);
                }),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('broker_id'),
            )
            ->allowedSorts('city', 'created_at', AllowedSort::field('name', 'search_name'))
            ->defaultSort(AllowedSort::field('name', 'search_name'))
            ->with('broker', 'user')
            ->withCount('contracts')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ClientResource::collection($clients);
    }

    /**
     * Create a client.
     */
    public function store(ClientRequest $request): ClientResource
    {
        /** @var User $user */
        $user = $request->user();

        $client = new Client($request->clientAttributes());
        $client->brokerage_id = $user->brokerage_id;
        $client->broker_id = $request->integer('broker_id', $user->id);
        $client->save();

        return new ClientResource($client->load('broker'));
    }

    /**
     * Show a client.
     */
    public function show(Client $client): ClientResource
    {
        return new ClientResource($client->load('broker', 'user')->loadCount('contracts'));
    }

    /**
     * Update a client.
     */
    public function update(ClientRequest $request, Client $client): ClientResource
    {
        $client->fill($request->clientAttributes());

        if ($request->has('broker_id')) {
            $client->broker_id = $request->integer('broker_id');
        }

        $client->save();

        return new ClientResource($client->load('broker'));
    }

    /**
     * Delete a client.
     *
     * Refused with a 409 while the client still has an active contract.
     */
    public function destroy(Client $client): Response
    {
        if ($client->contracts()->where('status', ContractStatus::Active)->exists()) {
            throw new ConflictHttpException(__('This client still has active contracts.'));
        }

        $client->delete();
        $client->user?->tokens()->delete();

        return response()->noContent();
    }
}
