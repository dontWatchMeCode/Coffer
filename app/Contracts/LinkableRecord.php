<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Team;

interface LinkableRecord
{
    public function linkableType(): string;

    /**
     * @return mixed
     */
    public function getKey();

    /**
     * @return array{type: string, id: int, title: string}
     */
    public function recordLinkContext(): array;

    /**
     * @return array<int, array{id: int, type: string, title: string, url: string}>
     */
    public function formattedLinkedRecords(Team $currentTeam): array;
}
