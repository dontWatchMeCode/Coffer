<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\LinkableRecord;
use App\Models\Team;

trait ProvidesRecordLinks
{
    /**
     * Build the record links payload for Inertia.
     *
     * @return array{links: array<int, array{id: int, type: string, title: string, url: string, preview: string|null}>, context: array{type: string, id: int, title: string}, endpoints: array{candidates: string, store: string, destroy: string}}
     */
    protected function recordLinksPayload(LinkableRecord $model, Team $currentTeam): array
    {
        return [
            'links' => $model->formattedLinkedRecords($currentTeam),
            'context' => $model->recordLinkContext(),
            'endpoints' => [
                'candidates' => route('team.links.candidates', ['current_team' => $currentTeam]),
                'store' => route('team.links.store', ['current_team' => $currentTeam]),
                'destroy' => route('team.links.destroy', ['current_team' => $currentTeam]),
            ],
        ];
    }
}
