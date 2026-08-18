<?php

declare(strict_types=1);

namespace App\Enums;

enum E2eeEpochStatus: string
{
    case Active = 'active';
    case Rotating = 'rotating';
    case Retired = 'retired';
}
