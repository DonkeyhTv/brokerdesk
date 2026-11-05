<?php

namespace App\Contracts;

interface Auditable
{
    public function auditBrokerageId(): int;

    public function auditLabel(): string;
}
