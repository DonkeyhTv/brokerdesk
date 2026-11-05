<?php

namespace App\Queries;

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ContractListQuery
{
    /**
     * The contracts a request may list, with its filters and sort applied.
     *
     * @return QueryBuilder<Contract>
     */
    public function for(Request $request): QueryBuilder
    {
        /** @var User $user */
        $user = $request->user();

        return QueryBuilder::for(Contract::query()->visibleTo($user)->with('client', 'insurer'), $request)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, mixed $value) {
                    $term = (string) $value;

                    $query->where(fn (Builder $contracts) => $contracts
                        ->where('policy_number', 'ilike', '%'.addcslashes($term, '%_\\').'%')
                        ->orWhereHas('client', function (Builder $clients) use ($term) {
                            /** @var Builder<Client> $clients */
                            $clients->search($term);
                        }));
                }),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('insurer_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::callback('expires_within', fn (Builder $query, mixed $value) => $query
                    ->where('status', ContractStatus::Active)
                    ->whereBetween('expires_on', [now()->toDateString(), now()->addDays((int) $value)->toDateString()])),
            )
            ->allowedSorts('expires_on', 'annual_premium_cents', 'policy_number', 'created_at')
            ->defaultSort('expires_on');
    }
}
