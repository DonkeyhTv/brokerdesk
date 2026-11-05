<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Load the demo data into an empty local database';

    public function handle(): int
    {
        if (! app()->isLocal()) {
            $this->components->info('Skipped: demo data is only loaded in the local environment.');

            return self::SUCCESS;
        }

        if (User::query()->exists()) {
            $this->components->info('Skipped: the database already contains data.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
