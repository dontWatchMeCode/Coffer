<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spreadsheets;

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\SpreadsheetWorkbook;
use App\Models\Team;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpreadsheetPageController extends Controller
{
    use EscapesLikeWildcards;
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $this->authorize('viewAny', SpreadsheetWorkbook::class);

        $spreadsheets = SpreadsheetWorkbook::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->when($request->string('search')->toString(), fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->orderByDesc('updated_at')
            ->simplePaginate(25);

        return Inertia::render('spreadsheets/Index', [
            'spreadsheets' => Inertia::scroll($spreadsheets->through(fn (SpreadsheetWorkbook $spreadsheet): array => $this->spreadsheetPayload($spreadsheet))),
        ]);
    }

    public function show(Team $currentTeam, int $spreadsheet): Response
    {
        $spreadsheet = SpreadsheetWorkbook::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($spreadsheet);

        $this->authorize('view', $spreadsheet);

        return Inertia::render('spreadsheets/Index', [
            'spreadsheet' => $this->spreadsheetPayload($spreadsheet, includeSnapshot: true),
            'recordLinks' => $this->recordLinksPayload($spreadsheet, $currentTeam, includeDrawingData: true),
            'recordTags' => $this->recordTagsPayload($spreadsheet, $currentTeam),
            'activityHistory' => $this->activityHistoryConfig($spreadsheet),
        ]);
    }

    public function trash(Request $request, Team $currentTeam): Response
    {
        $this->authorize('viewAny', SpreadsheetWorkbook::class);

        $spreadsheets = SpreadsheetWorkbook::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->when($request->string('search')->toString(), fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->orderByDesc('deleted_at')
            ->simplePaginate(25);

        return Inertia::render('spreadsheets/Trash', [
            'spreadsheets' => Inertia::scroll($spreadsheets->through(fn (SpreadsheetWorkbook $spreadsheet): array => $this->spreadsheetPayload($spreadsheet, includeDeletedAt: true))),
        ]);
    }

    /**
     * @param  Builder<SpreadsheetWorkbook>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->search($search, ['title'])
                ->orWhereHas('recordTags', function (Builder $tagQuery) use ($search): void {
                    $this->whereLikeEscaped($tagQuery, 'name', $this->likePattern($search));
                });
        });
    }

    /**
     * @return array{id: int, title: string, rowCount: int, columnCount: int, snapshot?: array<string, mixed>, tags: array<int, array{id: int, name: string, slug: string}>, createdAt: string|null, updatedAt: string|null, deletedAt?: string|null}
     */
    private function spreadsheetPayload(SpreadsheetWorkbook $spreadsheet, bool $includeSnapshot = false, bool $includeDeletedAt = false): array
    {
        $payload = [
            'id' => (int) $spreadsheet->id,
            'title' => $spreadsheet->title,
            'rowCount' => count($spreadsheet->snapshot['rows'] ?? []),
            'columnCount' => count($spreadsheet->snapshot['columns'] ?? []),
            'tags' => $spreadsheet->formattedRecordTags(),
            'createdAt' => $spreadsheet->created_at?->format(DateTimeInterface::ATOM),
            'updatedAt' => $spreadsheet->updated_at?->format(DateTimeInterface::ATOM),
        ];

        if ($includeSnapshot) {
            $payload['snapshot'] = $spreadsheet->snapshot;
        }

        if ($includeDeletedAt) {
            $deletedAt = $spreadsheet->getAttribute('deleted_at');
            $payload['deletedAt'] = $deletedAt instanceof DateTimeInterface ? $deletedAt->format(DateTimeInterface::ATOM) : null;
        }

        return $payload;
    }
}
