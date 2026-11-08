<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RenewContract
{
    /**
     * Extends the contract by one year from its current expiry date, which also
     * restarts its expiry alerts. The start date stays the date the contract began.
     */
    public function handle(Contract $contract, ?int $annualPremiumCents = null): Contract
    {
        if (! $contract->isRenewable()) {
            throw new ConflictHttpException(__('Only active or lapsed contracts renew, from :days days before they expire.', [
                'days' => Contract::RENEWAL_WINDOW_DAYS,
            ]));
        }

        $contract->expires_on = $contract->expires_on->addYearNoOverflow();
        $contract->status = ContractStatus::Active;

        if ($annualPremiumCents !== null) {
            $contract->annual_premium_cents = $annualPremiumCents;
        }

        $contract->save();

        return $contract;
    }
}
