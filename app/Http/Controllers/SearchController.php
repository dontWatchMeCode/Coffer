<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\ParsesSearchPrefixes;
use App\Concerns\SearchPrefixes;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Services\RecordLinkHelper;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use EscapesLikeWildcards;
    use ParsesSearchPrefixes;

    /**
     * Search across team records.
     */
    public function __invoke(Request $request, Team $currentTeam): JsonResponse
    {
        $rawQuery = $request->string('q')->trim()->toString();

        [$query, $scopes] = $this->parseSearchPrefix($rawQuery, SearchPrefixes::globalMap());

        if ($query === '') {
            return $this->emptySearchResponse();
        }

        $like = $this->likePattern($query);

        return response()->json([
            'tasks' => in_array('tasks', $scopes, true)
                ? $this->filterRoutable($this->searchTasks($currentTeam, $like))
                : [],
            'contacts' => in_array('contacts', $scopes, true)
                ? $this->filterRoutable($this->searchContacts($currentTeam, $like))
                : [],
            'events' => in_array('events', $scopes, true)
                ? $this->filterRoutable($this->searchEvents($currentTeam, $like))
                : [],
            'projects' => in_array('projects', $scopes, true)
                ? $this->filterRoutable($this->searchProjects($currentTeam, $like))
                : [],
            'bookmarks' => in_array('bookmarks', $scopes, true)
                ? $this->filterRoutable($this->searchBookmarks($currentTeam, $like))
                : [],
        ]);
    }

    /**
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    protected function searchTasks(Team $currentTeam, string $like): array
    {
        return Task::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'subtitle' => $task->description,
                'url' => RecordLinkHelper::urlForModel($task, $currentTeam),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    protected function searchContacts(Team $currentTeam, string $like): array
    {
        return Contact::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('address', 'like', $like)->orWhere('additional_info', 'like', $like))
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function (Contact $contact) use ($currentTeam): array {
                $phones = collect($contact->phone_numbers ?? [])
                    ->pluck('value')
                    ->filter()
                    ->implode(', ');
                $emails = collect($contact->email_addresses ?? [])
                    ->pluck('value')
                    ->filter()
                    ->implode(', ');
                $parts = array_filter([$phones, $emails]);

                return [
                    'id' => $contact->id,
                    'title' => $contact->name,
                    'subtitle' => $parts !== [] ? implode(' · ', $parts) : null,
                    'url' => RecordLinkHelper::urlForModel($contact, $currentTeam),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    protected function searchEvents(Team $currentTeam, string $like): array
    {
        return CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('date')
            ->limit(10)
            ->get()
            ->map(function (CalendarEvent $event) use ($currentTeam): array {
                $date = $event->getAttribute('date');

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'subtitle' => $date instanceof DateTimeInterface
                        ? $date->format('F j, Y')
                        : null,
                    'url' => RecordLinkHelper::urlForModel($event, $currentTeam),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    protected function searchProjects(Team $currentTeam, string $like): array
    {
        return Project::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->name,
                'subtitle' => $project->description,
                'url' => RecordLinkHelper::urlForModel($project, $currentTeam),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    protected function searchBookmarks(Team $currentTeam, string $like): array
    {
        return Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('url', 'like', $like))
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn (Bookmark $bookmark): array => [
                'id' => $bookmark->id,
                'title' => $bookmark->title,
                'subtitle' => $bookmark->description,
                'url' => RecordLinkHelper::urlForModel($bookmark, $currentTeam),
            ])
            ->values()
            ->all();
    }

    /**
     * Remove results that have no navigable URL.
     *
     * @param  array<int, array{id: int, title: string, subtitle: string|null, url: string}>  $results
     * @return array<int, array{id: int, title: string, subtitle: string|null, url: string}>
     */
    protected function filterRoutable(array $results): array
    {
        return array_values(array_filter($results, fn (array $item): bool => $item['url'] !== ''));
    }

    protected function emptySearchResponse(): JsonResponse
    {
        return response()->json([
            'tasks' => [],
            'contacts' => [],
            'events' => [],
            'projects' => [],
            'bookmarks' => [],
        ]);
    }
}
