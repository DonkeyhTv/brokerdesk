<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    /**
     * List the activity of the brokerage.
     */
    #[QueryParameter('filter[subject_type]', 'client, contract, claim or document.', type: 'string')]
    #[QueryParameter('filter[subject_id]', 'Only the activity of this record.', type: 'int')]
    #[QueryParameter('filter[action]', 'created, updated or deleted.', type: 'string')]
    #[QueryParameter('filter[user_id]', 'Only the activity of this person.', type: 'int')]
    #[QueryParameter('per_page', 'Between 1 and 100.', type: 'int', default: 25)]
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        /** @var User $admin */
        $admin = $request->user();

        $logs = QueryBuilder::for(ActivityLog::query()->where('brokerage_id', $admin->brokerage_id)->with('user'))
            ->allowedFilters(
                AllowedFilter::exact('subject_type'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('action'),
                AllowedFilter::exact('user_id'),
            )
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ActivityLogResource::collection($logs);
    }
}
