<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Settled = 'settled';
    case Rejected = 'rejected';

    public function isClosed(): bool
    {
        return $this === self::Settled || $this === self::Rejected;
    }
}
