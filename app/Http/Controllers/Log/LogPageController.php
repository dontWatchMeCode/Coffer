<?php

declare(strict_types=1);

namespace App\Http\Controllers\Log;

use App\Http\Controllers\Controller;
use App\Models\LogEntry;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class LogPageController extends Controller
{
    public function index(Team $currentTeam): Response
    {
        $entries = LogEntry::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('created_at')
            ->get();

        return Inertia::render('log/Index', [
            'entries' => $entries->map(fn (LogEntry $entry): array => [
                'id' => $entry->id,
                'body' => $entry->body,
                'category' => $entry->category,
                'createdAt' => $entry->created_at?->format(\DateTimeInterface::ATOM),
            ])->values()->all(),
        ]);
    }
}
