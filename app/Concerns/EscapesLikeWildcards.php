<?php

declare(strict_types=1);

namespace App\Concerns;

trait EscapesLikeWildcards
{
    protected function likePattern(string $value): string
    {
        return sprintf('%%%s%%', addcslashes($value, '%_\\'));
    }
}
