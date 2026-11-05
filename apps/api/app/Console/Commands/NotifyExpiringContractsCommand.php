<?php

namespace App\Console\Commands;

use App\Actions\Contracts\NotifyExpiringContracts;
use Illuminate\Console\Command;

class NotifyExpiringContractsCommand extends Command
{
    protected $signature = 'contracts:notify-expiring';

    protected $description = 'Alert brokers about contracts reaching 90, 60 and 30 days before expiry';

    public function handle(NotifyExpiringContracts $notifyExpiringContracts): int
    {
        $this->components->info($notifyExpiringContracts->handle().' alert(s) sent.');

        return self::SUCCESS;
    }
}
