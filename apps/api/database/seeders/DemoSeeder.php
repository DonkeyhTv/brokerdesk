<?php

namespace Database\Seeders;

use App\Enums\ContractStatus;
use App\Models\Brokerage;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Insurer;
use App\Models\User;
use App\Support\EnterpriseNumber;
use Database\Factories\Support\BelgianData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fictional demo data: two brokerages, so tenant isolation can be seen,
 * with contracts spread over the 30, 60 and 90 day renewal windows.
 */
class DemoSeeder extends Seeder
{
    private const array EXPIRY_WINDOWS = [[3, 30], [31, 60], [61, 90]];

    /**
     * @var Collection<int, Insurer>
     */
    private Collection $insurers;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo data can only be seeded in local and testing environments.');
        }

        fake()->seed(20261105);

        $this->insurers = new Collection(array_map(
            fn (string $name) => Insurer::factory()->create(['name' => $name]),
            BelgianData::INSURERS,
        ));

        $this->seedMainBrokerage();
        $this->seedSecondBrokerage();
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

        Claim::factory()->create(['contract_id' => $motor->id, 'declared_by' => $account->id]);
        Claim::factory()->settled()->create(['contract_id' => $home->id, 'declared_by' => $account->id]);
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
