<?php

declare(strict_types=1);

namespace App\Http\Controllers\Log;

use App\Http\Controllers\Controller;
use App\Models\LogEntry;
use App\Models\Team;
use App\Services\RecordTypeRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class LogPageController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $search = $request->string('search')->toString();
        $subjectType = RecordTypeRegistry::typeForClass(LogEntry::class);
        $categoryInput = $request->input('categories');
        $selectedCategories = collect(is_array($categoryInput) ? $categoryInput : [$request->string('category')->toString()])
            ->filter(fn (mixed $category): bool => is_string($category) && $category !== '')
            ->values()
            ->all();

        $entries = LogEntry::query()
            ->whereBelongsTo($currentTeam)
            ->when($search, fn ($q) => $q->search($search, ['body', 'category']))
            ->when($selectedCategories !== [], fn ($q) => $q->whereIn('category', $selectedCategories))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(25);

        $activityCounts = Activity::query()
            ->where('subject_type', (new LogEntry)->getMorphClass())
            ->whereIn('subject_id', $entries->getCollection()->pluck('id'))
            ->selectRaw('subject_id, count(*) as aggregate')
            ->groupBy('subject_id')
            ->pluck('aggregate', 'subject_id');

        $categories = LogEntry::query()
            ->whereBelongsTo($currentTeam)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values()
            ->all();

        return Inertia::render('log/Index', [
            'entries' => Inertia::scroll($entries->through(fn (LogEntry $entry): array => [
                'id' => $entry->id,
                'body' => $entry->body,
                'category' => $entry->category,
                'createdAt' => $entry->created_at?->format(\DateTimeInterface::ATOM),
                'activityHistory' => [
                    'subject_type' => $subjectType,
                    'subject_id' => $entry->id,
                    'total' => (int) ($activityCounts[$entry->id] ?? 0),
                ],
            ])),
            'categories' => $categories,
        ]);
    }

    public function trash(Request $request, Team $currentTeam): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        $entries = LogEntry::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->when($search, fn ($q) => $q->search($search, ['body', 'category']))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('deleted_at')
            ->simplePaginate(25);

        $categories = LogEntry::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values()
            ->all();

        return Inertia::render('log/Trash', [
            'entries' => Inertia::scroll($entries->through(function (LogEntry $entry): array {
                $deletedAt = $entry->getAttribute('deleted_at');

                return [
                    'id' => $entry->id,
                    'body' => $entry->body,
                    'category' => $entry->category,
                    'createdAt' => $entry->created_at?->format(\DateTimeInterface::ATOM),
                    'deletedAt' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(\DateTimeInterface::ATOM) : null,
                ];
            })),
            'categories' => $categories,
        ]);
    }
}
