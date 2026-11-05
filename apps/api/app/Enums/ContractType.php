<?php

namespace App\Enums;

enum ContractType: string
{
    case Motor = 'motor';
    case Home = 'home';
    case Liability = 'liability';
    case Life = 'life';
    case Health = 'health';
    case Legal = 'legal';
    case Business = 'business';
}
