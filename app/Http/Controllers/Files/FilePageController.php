<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\FileItem;
use App\Models\Team;
use App\Support\FileTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FilePageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $files = FileItem::query()
            ->whereBelongsTo($currentTeam)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['title', 'description', 'original_name']))
            ->orderByDesc('created_at')
            ->simplePaginate(25);

        return Inertia::render('files/Index', [
            'files' => Inertia::scroll($files->through(fn (FileItem $file): array => $this->filePayload($file, $currentTeam))),
            'uploadConstraints' => $this->uploadConstraints(),
        ]);
    }

    public function trash(Request $request, Team $currentTeam): Response
    {
        $files = FileItem::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['title', 'description', 'original_name']))
            ->orderByDesc('deleted_at')
            ->simplePaginate(25);

        return Inertia::render('files/Trash', [
            'files' => Inertia::scroll($files->through(function (FileItem $file) use ($currentTeam): array {
                $deletedAt = $file->getAttribute('deleted_at');

                return [
                    ...$this->filePayload($file, $currentTeam),
                    'deletedAt' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(\DateTimeInterface::ATOM) : null,
                ];
            })),
        ]);
    }

    public function show(Request $request, Team $currentTeam, int $file): Response
    {
        $file = FileItem::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($file);

        return Inertia::render('files/Index', [
            'files' => Inertia::optional(fn () => Inertia::scroll(
                FileItem::query()
                    ->whereBelongsTo($currentTeam)
                    ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['title', 'description', 'original_name']))
                    ->orderByDesc('created_at')
                    ->simplePaginate(25)
                    ->through(fn (FileItem $f): array => $this->filePayload($f, $currentTeam))
            )),
            'uploadConstraints' => Inertia::optional(fn (): array => $this->uploadConstraints()),
            'file' => $this->filePayload($file, $currentTeam, includeTextPreview: true),
            'recordLinks' => $this->recordLinksPayload($file, $currentTeam),
            'recordTags' => $this->recordTagsPayload($file, $currentTeam),
            'activityHistory' => $this->activityHistoryConfig($file),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(FileItem $file, Team $currentTeam, bool $includeTextPreview = false): array
    {
        $hasInlinePreview = $file->disk !== null && $file->path !== null
            && in_array($file->previewKind(), ['image', 'video'], true);

        return [
            'id' => $file->id,
            'title' => $file->title,
            'description' => $file->description,
            'originalName' => $file->original_name,
            'mimeType' => $file->mime_type,
            'size' => $file->size,
            'width' => $file->width,
            'height' => $file->height,
            'previewKind' => $file->previewKind(),
            'previewUrl' => $hasInlinePreview ? route('team.files.inline', ['current_team' => $currentTeam, 'file' => $file->id]) : null,
            'thumbnailUrl' => $hasInlinePreview && $file->previewKind() === 'video'
                ? route('team.files.thumbnail', ['current_team' => $currentTeam, 'file' => $file->id, 'v' => hash('xxh128', (string) $file->path)])
                : null,
            'downloadUrl' => route('team.files.download', ['current_team' => $currentTeam, 'file' => $file->id]),
            'createdAt' => $file->created_at?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $file->updated_at?->format(\DateTimeInterface::ATOM),
            ...($includeTextPreview ? ['textPreview' => $this->textPreview($file)] : []),
        ];
    }

    /** @return array{content: string, truncated: bool}|null */
    private function textPreview(FileItem $file): ?array
    {
        if ($file->previewKind() !== 'text' || $file->disk === null || $file->path === null) {
            return null;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->path)) {
            return null;
        }

        $stream = $disk->readStream($file->path);

        if (! is_resource($stream)) {
            return null;
        }

        try {
            $bytes = stream_get_contents($stream, FileTypes::TEXT_PREVIEW_BYTES + 1);
        } finally {
            fclose($stream);
        }

        if ($bytes === false) {
            return null;
        }

        $content = mb_strcut($bytes, 0, FileTypes::TEXT_PREVIEW_BYTES, 'UTF-8');

        return mb_check_encoding($content, 'UTF-8')
            ? ['content' => $content, 'truncated' => strlen($bytes) > FileTypes::TEXT_PREVIEW_BYTES]
            : null;
    }

    /**
     * @return array{acceptedMimeTypes: array<int, string>, acceptedExtensions: array<int, string>, maxKilobytes: int, maxMegabytes: int}
     */
    private function uploadConstraints(): array
    {
        return [
            'acceptedMimeTypes' => FileTypes::mimeTypes(),
            'acceptedExtensions' => FileTypes::extensions(),
            'maxKilobytes' => FileTypes::MAX_UPLOAD_KILOBYTES,
            'maxMegabytes' => FileTypes::MAX_UPLOAD_MEGABYTES,
        ];
    }
}
