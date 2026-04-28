<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\LinkableRecord;
use App\Models\Team;

trait ProvidesRecordTags
{
    /**
     * @return array{tags: array<int, array{id: int, name: string, slug: string}>, context: array{type: string, id: int, title: string}, endpoints: array{candidates: string, store: string, destroy: string}}
     */
    protected function recordTagsPayload(LinkableRecord $model, Team $currentTeam): array
    {
        return [
            'tags' => $model->formattedRecordTags(),
            'context' => $model->recordLinkContext(),
            'endpoints' => [
                'candidates' => route('team.tags.candidates', ['current_team' => $currentTeam]),
                'store' => route('team.tags.store', ['current_team' => $currentTeam]),
                'destroy' => route('team.tags.destroy', ['current_team' => $currentTeam]),
            ],
        ];
    }
}
