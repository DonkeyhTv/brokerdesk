<?php

namespace App\Enums;

enum PaymentFrequency: string
{
    case Annual = 'annual';
    case Semiannual = 'semiannual';
    case Quarterly = 'quarterly';
    case Monthly = 'monthly';
}
