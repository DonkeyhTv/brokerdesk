<?php

namespace App\Actions\Claims;

use App\Models\Claim;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateClaim
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ConflictHttpException when the claim is already closed
     */
    public function handle(Claim $claim, array $attributes): Claim
    {
        if ($claim->status->isClosed()) {
            throw new ConflictHttpException('A closed claim can no longer be changed.');
        }

        $claim->fill($attributes);
        $claim->closed_at = $claim->status->isClosed() ? now()->toImmutable() : null;
        $claim->save();

        return $claim;
    }
}
