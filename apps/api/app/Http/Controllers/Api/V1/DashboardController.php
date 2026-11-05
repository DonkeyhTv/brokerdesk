<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ClaimStatus;
use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Claim;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the key figures of the visible portfolio.
     *
     * An admin sees the whole brokerage, and a broker their own portfolio.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $active = fn () => Contract::query()->visibleTo($user)->where('status', ContractStatus::Active);
        $day = fn (int $days) => today()->addDays($days)->toDateString();

        /** @var object{within_30: int, from_31_to_60: int, from_61_to_90: int, contracts: int, premium: int|null} $totals */
        $totals = $active()->toBase()->selectRaw(
            'count(*) filter (where expires_on between ? and ?) as within_30,
             count(*) filter (where expires_on between ? and ?) as from_31_to_60,
             count(*) filter (where expires_on between ? and ?) as from_61_to_90,
             count(*) as contracts,
             sum(annual_premium_cents) as premium',
            [$day(0), $day(30), $day(31), $day(60), $day(61), $day(90)],
        )->first();

        $premiumByType = $active()->toBase()
            ->selectRaw('type, count(*) as contracts, sum(annual_premium_cents) as premium')
            ->groupBy('type')
            ->orderByDesc('premium')
            ->get()
            ->map(fn (object $row) => [
                'type' => $row->type,
                'contracts' => (int) $row->contracts,
                'annual_premium_cents' => (int) $row->premium,
            ]);

        $nextExpiries = $active()
            ->where('expires_on', '>=', $day(0))
            ->with('client', 'insurer')
            ->orderBy('expires_on')
            ->orderBy('id')
            ->limit(5)
            ->get();

        return response()->json([
            'expiring' => [
                'within_30' => (int) $totals->within_30,
                'from_31_to_60' => (int) $totals->from_31_to_60,
                'from_61_to_90' => (int) $totals->from_61_to_90,
            ],
            'open_claims' => Claim::query()->visibleTo($user)->whereIn('status', [ClaimStatus::Open, ClaimStatus::InReview])->count(),
            'active_contracts' => (int) $totals->contracts,
            'annual_premium_cents' => (int) $totals->premium,
            'premium_by_type' => $premiumByType,
            'next_expiries' => ContractResource::collection($nextExpiries),
        ]);
    }
}
