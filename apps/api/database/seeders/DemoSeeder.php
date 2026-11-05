<?php

namespace Database\Seeders;

use App\Enums\ContractStatus;
use App\Models\ActivityLog;
use App\Models\Brokerage;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Insurer;
use App\Models\User;
use App\Support\EnterpriseNumber;
use Database\Factories\Support\BelgianData;
use Database\Factories\Support\DemoPdf;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fictional demo data: two brokerages, so tenant isolation can be seen,
 * with contracts spread over the 30, 60 and 90 day renewal windows.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const array EXPIRY_WINDOWS = [[3, 30], [31, 60], [61, 90]];

    /**
     * @var Collection<int, Insurer>
     */
    private Collection $insurers;

    public static function isAllowed(): bool
    {
        return app()->environment(['local', 'testing']) || config('demo.enabled');
    }

    public function run(): void
    {
        if (! self::isAllowed()) {
            throw new RuntimeException('Demo data needs the local or testing environment, or demo mode.');
        }

        fake()->seed(20261105);

        $this->insurers = new Collection(array_map(
            fn (string $name) => Insurer::factory()->create(['name' => $name]),
            BelgianData::INSURERS,
        ));

        $this->seedMainBrokerage();
        $this->seedSecondBrokerage();
        $this->seedActivity();
    }

    private function seedMainBrokerage(): void
    {
        $brokerage = Brokerage::factory()->create([
            'name' => 'Cabinet Démo Meuse',
            'enterprise_number' => EnterpriseNumber::fromBase(9_000_001)->digits,
            'email' => 'contact@meuse.example.be',
            'street' => 'Rue de la Station 12',
            'postal_code' => '4000',
            'city' => 'Liège',
        ]);

        User::factory()->admin()->for($brokerage)->create(['name' => 'Demo Admin', 'email' => 'admin@example.be']);
        $demoBroker = User::factory()->for($brokerage)->create(['name' => 'Demo Broker', 'email' => 'broker@example.be']);
        $otherBrokers = User::factory()->count(2)->for($brokerage)->create();

        $this->seedDemoClient($brokerage, $demoBroker);
        $this->seedDemoCompany($brokerage, $demoBroker);
        $this->seedPortfolio($brokerage, $demoBroker, individuals: 25, companies: 8);

        foreach ($otherBrokers as $broker) {
            $this->seedPortfolio($brokerage, $broker, individuals: 10, companies: 3);
        }
    }

    private function seedSecondBrokerage(): void
    {
        $brokerage = Brokerage::factory()->create([
            'name' => 'Demo Makelaars Schelde',
            'enterprise_number' => EnterpriseNumber::fromBase(9_000_002)->digits,
            'email' => 'info@schelde.example.be',
            'street' => 'Stationsstraat 8',
            'postal_code' => '9000',
            'city' => 'Gent',
        ]);

        User::factory()->admin()->for($brokerage)->create(['name' => 'Schelde Admin', 'email' => 'admin@schelde.example.be']);
        $broker = User::factory()->for($brokerage)->create(['name' => 'Schelde Broker', 'email' => 'broker@schelde.example.be']);

        $this->seedPortfolio($brokerage, $broker, individuals: 10, companies: 5);
    }

    /**
     * The portal account of the README, with something to show on every screen.
     */
    private function seedDemoClient(Brokerage $brokerage, User $broker): void
    {
        $account = User::factory()->client()->for($brokerage)->create(['name' => 'Demo Client', 'email' => 'client@example.be']);

        $client = Client::factory()->create([
            'brokerage_id' => $brokerage->id,
            'broker_id' => $broker->id,
            'user_id' => $account->id,
            'first_name' => 'Demo',
            'last_name' => 'Client',
            'email' => 'client@example.be',
        ]);

        $motor = Contract::factory()->expiringInDays(25)->create($this->contractAttributes($client, ['type' => 'motor']));
        $home = Contract::factory()->create($this->contractAttributes($client, ['type' => 'home']));
        Contract::factory()->create($this->contractAttributes($client, ['type' => 'legal']));

        $this->seedDocuments($motor);
        $this->seedDocuments($home);

        Claim::factory()->create(['contract_id' => $motor->id, 'declared_by' => $account->id]);
        Claim::factory()->settled()->create(['contract_id' => $home->id, 'declared_by' => $account->id]);
    }

    /**
     * The same portal experience, for a client that is a company.
     */
    private function seedDemoCompany(Brokerage $brokerage, User $broker): void
    {
        $account = User::factory()->client()->for($brokerage)->create(['name' => 'Demo Company', 'email' => 'company@example.be']);

        $client = Client::factory()->company()->create([
            'brokerage_id' => $brokerage->id,
            'broker_id' => $broker->id,
            'user_id' => $account->id,
            'company_name' => 'Demo Company SRL',
            'email' => 'company@example.be',
        ]);

        $business = Contract::factory()->expiringInDays(50)->create($this->contractAttributes($client, ['type' => 'business']));
        $fleet = Contract::factory()->create($this->contractAttributes($client, ['type' => 'motor']));
        Contract::factory()->create($this->contractAttributes($client, ['type' => 'liability']));

        $this->seedDocuments($business);
        $this->seedDocuments($fleet);

        Claim::factory()->create(['contract_id' => $business->id, 'declared_by' => $account->id]);
        Claim::factory()->inReview()->create(['contract_id' => $fleet->id, 'declared_by' => $account->id]);
    }

    private function seedPortfolio(Brokerage $brokerage, User $broker, int $individuals, int $companies): void
    {
        $owner = ['brokerage_id' => $brokerage->id, 'broker_id' => $broker->id];

        $clients = Client::factory()->count($individuals)->create($owner)
            ->concat(Client::factory()->company()->count($companies)->create($owner));

        foreach ($clients as $client) {
            $this->seedContracts($client);
        }
    }

    private function seedContracts(Client $client): void
    {
        foreach (range(1, fake()->numberBetween(1, 3)) as $ignored) {
            $factory = Contract::factory();

            $factory = match (fake()->numberBetween(1, 10)) {
                1, 2, 3 => $factory->expiringInDays(fake()->numberBetween(...fake()->randomElement(self::EXPIRY_WINDOWS))),
                4 => $factory->withStatus(ContractStatus::Cancelled),
                5 => $factory->withStatus(ContractStatus::Expired)->expiringInDays(-fake()->numberBetween(1, 200)),
                default => $factory,
            };

            $contract = $factory->create($this->contractAttributes($client));

            if (fake()->boolean(25)) {
                $this->seedClaim($contract);
            }

            if (fake()->boolean(30)) {
                $this->seedDocuments($contract);
            }
        }
    }

    private function seedClaim(Contract $contract): void
    {
        $factory = Claim::factory();

        $factory = match (fake()->numberBetween(1, 4)) {
            1 => $factory->inReview(),
            2 => $factory->settled(),
            3 => $factory->rejected(),
            default => $factory,
        };

        $factory->create(['contract_id' => $contract->id]);
    }

    /**
     * One document for the client, and one internal note only staff can read.
     */
    private function seedDocuments(Contract $contract): void
    {
        foreach (['conditions-particulieres.pdf' => true, 'note-interne.pdf' => false] as $name => $visible) {
            $pdf = DemoPdf::make("{$contract->policy_number} - {$name}");
            $path = "documents/{$contract->brokerage_id}/".Str::uuid().'.pdf';
            Storage::disk('local')->put($path, $pdf);

            Document::factory()->create([
                'contract_id' => $contract->id,
                'original_name' => $name,
                'path' => $path,
                'size_bytes' => strlen($pdf),
                'visible_to_client' => $visible,
            ]);
        }
    }

    /**
     * Model events are off while seeding, so the journal starts with a few entries of its own.
     */
    private function seedActivity(): void
    {
        $broker = User::query()->where('email', 'broker@example.be')->firstOrFail();

        $contracts = Contract::query()->where('brokerage_id', $broker->brokerage_id)->latest('id')->limit(12)->get();

        foreach ($contracts as $index => $contract) {
            $log = new ActivityLog;
            $log->forceFill([
                'brokerage_id' => $contract->brokerage_id,
                'user_id' => $broker->id,
                'action' => $index % 3 === 0 ? 'updated' : 'created',
                'subject_type' => $contract->getMorphClass(),
                'subject_id' => $contract->id,
                'subject_label' => $contract->policy_number,
                'changes' => $index % 3 === 0 ? ['annual_premium_cents' => ['old' => $contract->annual_premium_cents - 2500, 'new' => $contract->annual_premium_cents]] : null,
                'created_at' => now()->subHours($index * 5 + 1),
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function contractAttributes(Client $client, array $overrides = []): array
    {
        return [
            'client_id' => $client->id,
            'insurer_id' => $this->insurers->random()->id,
            ...$overrides,
        ];
    }
}
