<?php

namespace App\Actions\Claims;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeclareClaim
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Contract $contract, User $declarer, array $attributes): Claim
    {
        return DB::transaction(function () use ($contract, $declarer, $attributes) {
            // Serializes declarations per brokerage, so that two of them cannot get the same reference.
            DB::select('SELECT pg_advisory_xact_lock(?)', [$contract->brokerage_id]);

            $claim = new Claim($attributes);
            $claim->forceFill([
                'brokerage_id' => $contract->brokerage_id,
                'contract_id' => $contract->id,
                'declared_by' => $declarer->id,
                'reference' => $this->nextReference($contract->brokerage_id),
                'status' => ClaimStatus::Open,
            ])->save();

            return $claim;
        });
    }

    private function nextReference(int $brokerageId): string
    {
        $prefix = 'SIN-'.now()->format('Y').'-';

        $last = Claim::query()
            ->where('brokerage_id', $brokerageId)
            ->where('reference', 'like', $prefix.'%')
            ->max('reference');

        $number = is_string($last) ? (int) substr($last, strlen($prefix)) : 0;

        return $prefix.str_pad((string) ($number + 1), 6, '0', STR_PAD_LEFT);
    }
}
