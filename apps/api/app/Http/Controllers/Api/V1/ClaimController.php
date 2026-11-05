<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Claims\DeclareClaim;
use App\Actions\Claims\UpdateClaim;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClaimRequest;
use App\Http\Requests\UpdateClaimRequest;
use App\Http\Resources\ClaimResource;
use App\Models\Claim;
use App\Models\Contract;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClaimController extends Controller
{
    /**
     * List claims.
     */
    #[QueryParameter('filter[search]', 'Claim reference.', type: 'string')]
    #[QueryParameter('filter[status]', 'open, in_review, settled or rejected.', type: 'string')]
    #[QueryParameter('filter[contract_id]', 'Only the claims of this contract.', type: 'int')]
    #[QueryParameter('sort', 'occurred_on, reference, status or created_at; prefix with - to reverse.', type: 'string')]
    #[QueryParameter('per_page', 'Between 1 and 100.', type: 'int', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $claims = QueryBuilder::for(Claim::query()->visibleTo($user))
            ->allowedFilters(
                AllowedFilter::callback('search', fn ($query, $value) => $query->where('reference', 'ilike', '%'.addcslashes((string) $value, '%_\\').'%')),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('contract_id'),
            )
            ->allowedSorts('occurred_on', 'reference', 'status', 'created_at')
            ->defaultSort('-occurred_on')
            ->with('contract.client', 'contract.insurer', 'declarer')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ClaimResource::collection($claims);
    }

    /**
     * Declare a claim.
     *
     * Clients may declare claims on their own active contracts.
     */
    public function store(StoreClaimRequest $request, DeclareClaim $declareClaim): ClaimResource
    {
        /** @var User $user */
        $user = $request->user();
        $contract = Contract::query()->findOrFail($request->integer('contract_id'));

        $claim = $declareClaim->handle($contract, $user, $request->safe()->except('contract_id'));

        return new ClaimResource($claim->load('contract.client', 'contract.insurer', 'declarer'));
    }

    /**
     * Show a claim.
     */
    public function show(Claim $claim): ClaimResource
    {
        return new ClaimResource($claim->load('contract.client', 'contract.insurer', 'declarer'));
    }

    /**
     * Update a claim.
     *
     * Moving a claim to settled or rejected closes it. A closed claim answers 409.
     */
    public function update(UpdateClaimRequest $request, Claim $claim, UpdateClaim $updateClaim): ClaimResource
    {
        $updateClaim->handle($claim, ['settled_amount_cents' => null, ...$request->validated()]);

        return new ClaimResource($claim->load('contract.client', 'contract.insurer', 'declarer'));
    }

    /**
     * Delete a claim.
     */
    public function destroy(Claim $claim): Response
    {
        $claim->delete();

        return response()->noContent();
    }
}
