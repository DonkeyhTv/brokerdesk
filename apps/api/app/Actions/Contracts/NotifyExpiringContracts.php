<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Notifications\ContractExpiring;
use Illuminate\Database\Eloquent\Builder;

class NotifyExpiringContracts
{
    private const array THRESHOLDS = [30, 60, 90];

    /**
     * Alert brokers once per contract and threshold. A renewed contract has a
     * new expiry date, and therefore starts its alerts again.
     *
     * @return int the number of alerts sent
     */
    public function handle(): int
    {
        $today = today();
        $sent = 0;

        $contracts = Contract::query()
            ->where('status', ContractStatus::Active)
            ->whereBetween('expires_on', [$today->toDateString(), $today->copy()->addDays(max(self::THRESHOLDS))->toDateString()])
            ->whereHas('client', fn (Builder $clients) => $clients->whereNull('deleted_at'))
            ->with('client.broker');

        foreach ($contracts->lazyById(200) as $contract) {
            $threshold = $this->thresholdFor((int) $today->diffInDays($contract->expires_on));

            $alert = $contract->expiryAlerts()->firstOrCreate([
                'threshold_days' => $threshold,
                'expires_on' => $contract->expires_on->toDateString(),
            ]);

            if ($alert->wasRecentlyCreated) {
                $contract->client->broker->notify(new ContractExpiring($contract, $threshold));
                $sent++;
            }
        }

        return $sent;
    }

    private function thresholdFor(int $days): int
    {
        foreach (self::THRESHOLDS as $threshold) {
            if ($days <= $threshold) {
                return $threshold;
            }
        }

        return max(self::THRESHOLDS);
    }
}
