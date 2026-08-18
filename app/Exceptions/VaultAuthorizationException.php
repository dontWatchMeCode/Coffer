<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class VaultAuthorizationException extends RuntimeException
{
    public static function unauthenticated(): self
    {
        return new self('Authentication required.');
    }

    public static function noTeam(): self
    {
        return new self('Current team required.');
    }
}
