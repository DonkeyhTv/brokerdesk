<?php

namespace App\Policies\Concerns;

use Illuminate\Auth\Access\Response;

trait ConcealsRecords
{
    /**
     * Records a user may not see answer 404, so that their existence stays hidden.
     */
    protected function conceal(bool $visible, bool $allowed = true): Response
    {
        if (! $visible) {
            return Response::denyAsNotFound();
        }

        return $allowed ? Response::allow() : Response::deny();
    }
}
