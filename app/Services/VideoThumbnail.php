<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FileItem;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessStartFailedException;

class VideoThumbnail
{
    public static function pathFor(string $sourcePath): string
    {
        return $sourcePath.'.thumbnail.jpg';
    }

    public static function storyboardPathFor(string $sourcePath): string
    {
        return $sourcePath.'.storyboard.jpg';
    }

    public static function metadataPathFor(string $sourcePath): string
    {
        return $sourcePath.'.storyboard.json';
    }

    /** @return list<string> */
    public static function pathsFor(string $sourcePath): array
    {
        return [self::pathFor($sourcePath), self::storyboardPathFor($sourcePath), self::metadataPathFor($sourcePath)];
    }

    public function forFile(FileItem $file): ?string
    {
        return $this->generate($file, false);
    }

    /** @return array{path: string, duration: float, count: int, columns: int, width: int, height: int}|null */
    public function storyboardForFile(FileItem $file): ?array
    {
        $path = $this->generate($file, true);

        if ($path === null || $file->disk === null || $file->path === null) {
            return null;
        }

        try {
            $disk = Storage::disk($file->disk);
            $json = $disk->get(self::metadataPathFor($file->path));
        } catch (FileNotFoundException) {
            return null;
        }

        $metadata = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($metadata)) {
            $disk->delete([$path, self::metadataPathFor($file->path)]);

            return null;
        }

        $stored = $this->storedLayout($metadata);

        if ($stored !== null) {
            return ['path' => $path, ...$stored];
        }

        // Legacy sprites recorded duration only; recompute the grid.
        $duration = $this->validDuration($metadata['duration'] ?? null);

        if ($duration === null) {
            $disk->delete([$path, self::metadataPathFor($file->path)]);

            return null;
        }

        return ['path' => $path, ...$this->layout($duration)];
    }

    /** @return array{duration: float, count: int, columns: int, width: int, height: int} */
    private function layout(float $duration): array
    {
        $count = min(30, max(1, (int) ceil($duration / 5)));

        return ['duration' => $duration, 'count' => $count, 'columns' => min(5, $count), 'width' => 160, 'height' => 90];
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{duration: float, count: int, columns: int, width: int, height: int}|null */
    private function storedLayout(array $metadata): ?array
    {
        $duration = $this->validDuration($metadata['duration'] ?? null);
        $count = $metadata['count'] ?? null;
        $columns = $metadata['columns'] ?? null;
        $width = $metadata['width'] ?? null;
        $height = $metadata['height'] ?? null;

        if ($duration === null) {
            return null;
        }

        if (! is_int($count) || $count < 1 || $count > 30 || ! is_int($columns) || $columns < 1 || $columns > 5 || $columns > $count) {
            return null;
        }

        if (! is_int($width) || ! is_int($height) || $width < 16 || $width > 1920 || $height < 16 || $height > 1080) {
            return null;
        }

        return ['duration' => $duration, 'count' => $count, 'columns' => $columns, 'width' => $width, 'height' => $height];
    }

    private function validDuration(mixed $duration): ?float
    {
        if (! is_numeric($duration) || ! is_finite((float) $duration) || (float) $duration <= 0 || (float) $duration > 86_400) {
            return null;
        }

        return (float) $duration;
    }

    /** @return array{duration: float, count: int, columns: int, width: int, height: int}|null */
    private function probe(FileItem $file, string $path): ?array
    {
        $result = Process::timeout(5)->run([
            'ffprobe', '-v', 'error', '-protocol_whitelist', 'file',
            // Only mp4/webm reach video previews (see FileTypes), so the demuxer is always one of these two.
            '-f', $file->mime_type === 'video/mp4' ? 'mov' : 'matroska',
            '-show_entries', 'format=duration', '-of', 'json', $path,
        ]);
        $metadata = json_decode($result->output(), true);
        $duration = $this->validDuration(is_array($metadata) ? ($metadata['format']['duration'] ?? null) : null);

        if ($result->failed() || $duration === null) {
            return null;
        }

        return $this->layout($duration);
    }

    private function generate(FileItem $file, bool $storyboard): ?string
    {
        if ($file->previewKind() !== 'video' || $file->disk === null || $file->path === null) {
            return null;
        }

        $disk = Storage::disk($file->disk);
        $source = $file->path;
        $thumbnail = $storyboard ? self::storyboardPathFor($source) : self::pathFor($source);
        $metadataPath = self::metadataPathFor($source);
        $cacheKey = 'video-thumbnail:'.hash('sha256', $file->disk.':'.$thumbnail);

        if (! $this->freshFileExists($disk, $source)) {
            return null;
        }

        if ($this->freshFileExists($disk, $thumbnail) && (! $storyboard || $this->freshFileExists($disk, $metadataPath))) {
            return $thumbnail;
        }

        try {
            return Cache::lock($cacheKey, $storyboard ? 45 : 15)->block($storyboard ? 32 : 12, function () use ($disk, $source, $thumbnail, $metadataPath, $cacheKey, $file, $storyboard): ?string {
                if ($this->freshFileExists($disk, $thumbnail) && (! $storyboard || $this->freshFileExists($disk, $metadataPath))) {
                    return $thumbnail;
                }

                if (Cache::has($cacheKey.':failed') || ! $this->freshFileExists($disk, $source)) {
                    return null;
                }

                $temporary = $thumbnail.'.'.Str::uuid().'.tmp';
                $metadataTemporary = $temporary.'.json';

                try {
                    $layout = $storyboard ? $this->probe($file, $disk->path($source)) : null;

                    if ($storyboard && $layout === null) {
                        Cache::put($cacheKey.':failed', true, 300);

                        return null;
                    }

                    $filter = $layout === null ? 'scale=min(640\,iw):-2' : 'fps='.$layout['count'].'/'.sprintf('%.6F', $layout['duration'])
                        .',scale=160:90:force_original_aspect_ratio=decrease,pad=160:90:(ow-iw)/2:(oh-ih)/2,tile='.$layout['columns'].'x'.(int) ceil($layout['count'] / $layout['columns']);
                    $result = Process::timeout($storyboard ? 20 : 10)->run([
                        'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error',
                        '-protocol_whitelist', 'file', '-threads', '1',
                        // Only mp4/webm reach video previews (see FileTypes), so the demuxer is always one of these two.
                        '-f', $file->mime_type === 'video/mp4' ? 'mov' : 'matroska',
                        '-i', $disk->path($source),
                        '-frames:v', '1', '-an',
                        '-vf', $filter,
                        '-c:v', 'mjpeg', '-threads', '1', '-q:v', '3', '-f', 'image2',
                        $disk->path($temporary),
                    ]);

                    if ($result->failed() || ! $this->freshFileExists($disk, $temporary)) {
                        Cache::put($cacheKey.':failed', true, 300);

                        return null;
                    }

                    $dimensions = @getimagesize($disk->path($temporary));

                    if ($dimensions === false || $dimensions[2] !== IMAGETYPE_JPEG) {
                        Cache::put($cacheKey.':failed', true, 300);

                        return null;
                    }

                    if (! $this->freshFileExists($disk, $source) || ! $this->sourceRecordExists($file->id, $source)) {
                        return null;
                    }

                    if ($layout !== null && ! $disk->put($metadataTemporary, json_encode($layout, JSON_THROW_ON_ERROR))) {
                        return null;
                    }

                    if (! $disk->move($temporary, $thumbnail)) {
                        return null;
                    }

                    if ($layout !== null && ! $disk->move($metadataTemporary, $metadataPath)) {
                        $disk->delete($thumbnail);

                        return null;
                    }

                    if (! $this->freshFileExists($disk, $source) || ! $this->sourceRecordExists($file->id, $source)) {
                        $disk->delete($storyboard ? [$thumbnail, $metadataPath] : [$thumbnail]);

                        return null;
                    }

                    return $thumbnail;
                } catch (ProcessTimedOutException|ProcessStartFailedException) {
                    Cache::put($cacheKey.':failed', true, 300);

                    return null;
                } finally {
                    $disk->delete([$temporary, $metadataTemporary]);
                }
            });
        } catch (LockTimeoutException) {
            return null;
        }
    }

    /**
     * Other requests can replace or delete the database row between checks.
     *
     * @phpstan-impure
     */
    private function sourceRecordExists(int $fileId, string $source): bool
    {
        return FileItem::query()->whereKey($fileId)->where('path', $source)->exists();
    }

    /**
     * Other requests and FFmpeg can change local files between checks.
     *
     * @phpstan-impure
     */
    private function freshFileExists(FilesystemAdapter $disk, string $path): bool
    {
        clearstatcache(true, $disk->path($path));

        return $disk->exists($path);
    }
}
