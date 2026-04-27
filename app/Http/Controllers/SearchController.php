<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Concerns\EscapesLikeWildcards;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use EscapesLikeWildcards;

    /**
     * Search across team records.
     */
    public function __invoke(Request $request, Team $currentTeam): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();

        if ($query === '') {
            return response()->json([
                'tasks' => [],
                'contacts' => [],
                'events' => [],
                'projects' => [],
                'bookmarks' => [],
            ]);
        }

        $like = $this->likePattern($query);

        $tasks = Task::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('title')
            ->limit(10)
            ->get(['id', 'project_id', 'title', 'description'])
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'subtitle' => $task->description,
                'url' => route('team.tasks.edit', ['current_team' => $currentTeam, 'project' => $task->project_id, 'task' => $task->id]),
            ])
            ->values()
            ->all();

        $contacts = Contact::query()
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
                    'url' => route('team.contacts.show', ['current_team' => $currentTeam, 'contact' => $contact->id]),
                ];
            })
            ->values()
            ->all();

        $events = CalendarEvent::query()
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
                    'url' => route('team.calendar.events.edit', ['current_team' => $currentTeam, 'event' => $event->id]),
                ];
            })
            ->values()
            ->all();

        $projects = Project::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'description'])
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->name,
                'subtitle' => $project->description,
                'url' => route('team.tasks.show', ['current_team' => $currentTeam, 'project' => $project->id]),
            ])
            ->values()
            ->all();

        $bookmarks = Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('url', 'like', $like))
            ->orderBy('title')
            ->limit(10)
            ->get(['id', 'title', 'url', 'description'])
            ->map(fn (Bookmark $bookmark): array => [
                'id' => $bookmark->id,
                'title' => $bookmark->title,
                'subtitle' => $bookmark->description,
                'url' => route('team.bookmarks.show', ['current_team' => $currentTeam, 'bookmark' => $bookmark->id]),
            ])
            ->values()
            ->all();

        return response()->json([
            'tasks' => $tasks,
            'contacts' => $contacts,
            'events' => $events,
            'projects' => $projects,
            'bookmarks' => $bookmarks,
        ]);
    }
}
