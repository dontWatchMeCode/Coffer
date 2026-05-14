<?php

declare(strict_types=1);

namespace App\Http\Controllers\Log;

use App\Http\Controllers\Controller;
use App\Models\LogEntry;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogPageController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        $entries = LogEntry::query()
            ->whereBelongsTo($currentTeam)
            ->when($search, fn ($q) => $q->search($search, ['body', 'category']))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('created_at')
            ->simplePaginate(25);

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
            ])),
            'categories' => $categories,
        ]);
    }
}
