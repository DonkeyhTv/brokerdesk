<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Contracts\RenewContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\RenewContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;

class ContractRenewalController extends Controller
{
    /**
     * Renew a contract for one year.
     *
     * Open from 90 days before expiry, for active or lapsed contracts; refused with a 409 otherwise.
     */
    public function __invoke(RenewContractRequest $request, Contract $contract, RenewContract $renew): ContractResource
    {
        $premium = $request->filled('annual_premium_cents') ? $request->integer('annual_premium_cents') : null;

        return new ContractResource($renew->handle($contract, $premium)->load('client', 'insurer'));
    }
}
