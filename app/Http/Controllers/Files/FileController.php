<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Files\SaveFileRequest;
use App\Models\FileItem;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
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

        [$width, $height] = $this->imageDimensions($uploadedFile);

        try {
            $file = DB::transaction(fn () => FileItem::create([
                ...$request->safe()->except('file'),
                'team_id' => $currentTeam->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
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
        ];
    }
}
