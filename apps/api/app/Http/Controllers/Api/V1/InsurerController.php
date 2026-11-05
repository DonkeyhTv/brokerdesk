<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InsurerResource;
use App\Models\Insurer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InsurerController extends Controller
{
    /**
     * List insurers.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return InsurerResource::collection(Insurer::query()->orderBy('name')->get());
    }
}
