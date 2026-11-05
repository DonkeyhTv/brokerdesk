<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Client;
use App\Models\Contract;
use App\Queries\ContractListQuery;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ContractController extends Controller
{
    /**
     * List contracts.
     */
    #[QueryParameter('filter[search]', 'Policy number or client name.', type: 'string')]
    #[QueryParameter('filter[status]', 'active, suspended, cancelled or expired.', type: 'string')]
    #[QueryParameter('filter[type]', 'motor, home, liability, life, health, legal or business.', type: 'string')]
    #[QueryParameter('filter[insurer_id]', 'Only the contracts of this insurer.', type: 'int')]
    #[QueryParameter('filter[client_id]', 'Only the contracts of this client.', type: 'int')]
    #[QueryParameter('filter[expires_within]', 'Active contracts expiring within this number of days.', type: 'int')]
    #[QueryParameter('sort', 'expires_on, annual_premium_cents, policy_number or created_at; prefix with - to reverse.', type: 'string')]
    #[QueryParameter('per_page', 'Between 1 and 100.', type: 'int', default: 25)]
    public function index(Request $request, ContractListQuery $query): AnonymousResourceCollection
    {
        $contracts = $query->for($request)
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ContractResource::collection($contracts);
    }

    /**
     * Create a contract.
     */
    public function store(ContractRequest $request): ContractResource
    {
        $client = Client::query()->findOrFail($request->integer('client_id'));

        $contract = new Contract($request->safe()->except('client_id'));
        $contract->client_id = $client->id;
        $contract->brokerage_id = $client->brokerage_id;
        $contract->save();

        return new ContractResource($contract->load('client', 'insurer'));
    }

    /**
     * Show a contract.
     */
    public function show(Contract $contract): ContractResource
    {
        return new ContractResource($contract->load('client', 'insurer'));
    }

    /**
     * Update a contract.
     */
    public function update(ContractRequest $request, Contract $contract): ContractResource
    {
        $contract->update($request->validated());

        return new ContractResource($contract->load('client', 'insurer'));
    }

    /**
     * Delete a contract.
     *
     * Refused with a 409 while the contract still has an unresolved claim.
     */
    public function destroy(Contract $contract): Response
    {
        if ($contract->claims()->whereIn('status', [ClaimStatus::Open, ClaimStatus::InReview])->exists()) {
            throw new ConflictHttpException('This contract still has unresolved claims.');
        }

        $contract->delete();

        return response()->noContent();
    }
}
