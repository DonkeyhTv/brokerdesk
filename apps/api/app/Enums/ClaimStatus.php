<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Settled = 'settled';
    case Rejected = 'rejected';
}
