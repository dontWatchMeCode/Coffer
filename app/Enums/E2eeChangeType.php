<?php

declare(strict_types=1);

namespace App\Enums;

enum E2eeChangeType: string
{
    case Record = 'record';
    case KeyEpoch = 'key_epoch';
    case File = 'file';
}
