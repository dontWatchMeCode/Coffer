<?php

declare(strict_types=1);

namespace App\Support;

readonly class UserTeam
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $isPersonal,
        public ?string $role,
        public ?string $roleLabel,
        /** @var array<string, bool> */
        public array $featureSettings,
        public ?bool $isCurrent = null,
    ) {
        //
    }
}
