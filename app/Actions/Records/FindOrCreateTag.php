<?php

declare(strict_types=1);

namespace App\Actions\Records;

use App\Models\Tag;
use App\Models\Team;

class FindOrCreateTag
{
    public function execute(Team $team, string $name): ?Tag
    {
        $name = trim($name);
        $slug = Tag::slugFor($name);

        if ($name === '' || $slug === '') {
            return null;
        }

        return Tag::query()->firstOrCreate([
            'team_id' => $team->id,
            'slug' => $slug,
        ], [
            'name' => $name,
        ]);
    }
}
