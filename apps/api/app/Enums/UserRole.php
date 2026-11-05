<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Broker = 'broker';
    case Client = 'client';
}
