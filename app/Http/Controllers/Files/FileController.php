<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Files\SaveFileRequest;
use App\Models\FileItem;
use App\Models\Team;
use App\Services\VideoThumbnail;
use App\Support\FileTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    use HandlesTrashedRecords;

    public function store(SaveFileRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', FileItem::class);

        $uploadedFile = $request->file('file');
        abort_unless($uploadedFile instanceof UploadedFile, 422);

        $path = $uploadedFile->store('files/'.$currentTeam->id, 'local');
        abort_if($path === false, 500);

        $mimeType = $uploadedFile->getMimeType() ?? 'application/octet-stream';
        [$width, $height] = FileTypes::previewKind($mimeType) === 'image' ? $this->imageDimensions($uploadedFile) : [null, null];

        try {
            $file = DB::transaction(fn () => FileItem::create([
                ...$request->safe()->except('file'),
                'team_id' => $currentTeam->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $uploadedFile->getSize() ?: 0,
                'width' => $width,
                'height' => $height,
            ]));
        } catch (\Throwable $throwable) {
            Storage::disk('local')->delete($path);

            throw $throwable;
        }

        return to_route('team.files.show', [
            'current_team' => $currentTeam,
            'file' => $file->id,
        ]);
    }

    public function update(SaveFileRequest $request, Team $currentTeam, int $file): RedirectResponse
    {
        $file = FileItem::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($file);

        $this->authorize('update', $file);

        $file->update($request->safe()->except('file'));

        return to_route('team.files.show', [
            'current_team' => $currentTeam,
            'file' => $file->id,
        ]);
    }

    public function inline(Team $currentTeam, int $file): BinaryFileResponse
    {
        $file = FileItem::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($file);

        $this->authorize('view', $file);
        abort_unless(in_array($file->previewKind(), ['image', 'video'], true), 404);
        abort_if($file->disk === null || $file->path === null, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $response = response()->file(Storage::disk($file->disk)->path($file->path), $this->headers($file));
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        return $response;
    }

    public function download(Team $currentTeam, int $file): BinaryFileResponse
    {
        $file = FileItem::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($file);

        $this->authorize('view', $file);
        abort_if($file->disk === null || $file->path === null, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $response = response()->download(Storage::disk($file->disk)->path($file->path), $file->original_name, $this->headers($file));
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        return $response;
    }

    public function thumbnail(Team $currentTeam, int $file, VideoThumbnail $thumbnails): BinaryFileResponse
    {
        $file = FileItem::query()->whereBelongsTo($currentTeam)->findOrFail($file);
        $this->authorize('view', $file);
        $path = $thumbnails->forFile($file);
        abort_if($path === null || $file->disk === null, 404);

        $response = response()->file(Storage::disk($file->disk)->path($path), [
            ...$this->headers($file),
            'Content-Type' => 'image/jpeg',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        return $response;
    }

    public function storyboard(Team $currentTeam, int $file, VideoThumbnail $thumbnails): Response
    {
        $file = FileItem::query()->whereBelongsTo($currentTeam)->findOrFail($file);
        $this->authorize('view', $file);
        $storyboard = $thumbnails->storyboardForFile($file);
        abort_if($storyboard === null, 404);
        $imageUrl = route('team.files.storyboard-image', ['current_team' => $currentTeam, 'file' => $file->id, 'v' => hash('xxh128', (string) $file->path)]);
        $vtt = "WEBVTT\n\n";

        for ($index = 0; $index < $storyboard['count']; $index++) {
            $start = $index * $storyboard['duration'] / $storyboard['count'];
            $end = ($index + 1) * $storyboard['duration'] / $storyboard['count'];
            $x = ($index % $storyboard['columns']) * $storyboard['width'];
            $y = intdiv($index, $storyboard['columns']) * $storyboard['height'];
            $vtt .= $this->vttTime($start).' --> '.$this->vttTime($end)."\n".$imageUrl.'#xywh='.$x.','.$y.','.$storyboard['width'].','.$storyboard['height']."\n\n";
        }

        $response = response($vtt, 200, [
            ...$this->headers($file),
            'Content-Type' => 'text/vtt; charset=UTF-8',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        return $response;
    }

    public function storyboardImage(Team $currentTeam, int $file, VideoThumbnail $thumbnails): BinaryFileResponse
    {
        $file = FileItem::query()->whereBelongsTo($currentTeam)->findOrFail($file);
        $this->authorize('view', $file);
        $storyboard = $thumbnails->storyboardForFile($file);
        abort_if($storyboard === null || $file->disk === null, 404);
        abort_unless(Storage::disk($file->disk)->exists($storyboard['path']), 404);
        $response = response()->file(Storage::disk($file->disk)->path($storyboard['path']), [
            ...$this->headers($file),
            'Content-Type' => 'image/jpeg',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        return $response;
    }

    private function vttTime(float $seconds): string
    {
        $milliseconds = (int) round($seconds * 1000);

        return sprintf('%02d:%02d:%02d.%03d', intdiv($milliseconds, 3_600_000), intdiv($milliseconds, 60_000) % 60, intdiv($milliseconds, 1000) % 60, $milliseconds % 1000);
    }

    public function destroy(Team $currentTeam, int $file): RedirectResponse
    {
        $file = FileItem::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($file);

        $this->authorize('delete', $file);

        $file->delete();

        return to_route('team.files.index', [
            'current_team' => $currentTeam,
        ]);
    }

    public function restore(Team $currentTeam, int $file): RedirectResponse
    {
        return $this->restoreTrashedRecord($currentTeam, $file, FileItem::class, 'team.files.trash');
    }

    public function forceDestroy(Team $currentTeam, int $file): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $file, FileItem::class, 'team.files.trash');
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $dimensions = is_string($path) ? @getimagesize($path) : false;

        return is_array($dimensions) ? [$dimensions[0], $dimensions[1]] : [null, null];
    }

    /**
     * @return array<string, string>
     */
    private function headers(FileItem $file): array
    {
        return [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'; frame-ancestors 'none'",
        ];
    }
}
