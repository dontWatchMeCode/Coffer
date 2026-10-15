<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FileItem;
use Illuminate\Contracts\Cache\LockTimeoutException;
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

    public function forFile(FileItem $file): ?string
    {
        if ($file->previewKind() !== 'video' || $file->disk === null || $file->path === null) {
            return null;
        }

        $disk = Storage::disk($file->disk);
        $source = $file->path;
        $thumbnail = self::pathFor($source);
        $cacheKey = 'video-thumbnail:'.hash('sha256', $file->disk.':'.$source);

        if (! $this->freshFileExists($disk, $source)) {
            return null;
        }

        if ($this->freshFileExists($disk, $thumbnail)) {
            return $thumbnail;
        }

        try {
            return Cache::lock($cacheKey, 15)->block(12, function () use ($disk, $source, $thumbnail, $cacheKey, $file): ?string {
                if ($this->freshFileExists($disk, $thumbnail)) {
                    return $thumbnail;
                }

                if (Cache::has($cacheKey.':failed') || ! $this->freshFileExists($disk, $source)) {
                    return null;
                }

                $temporary = $thumbnail.'.'.Str::uuid().'.tmp';

                try {
                    $result = Process::timeout(10)->run([
                        'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error',
                        '-protocol_whitelist', 'file', '-threads', '1',
                        '-f', $file->mime_type === 'video/mp4' ? 'mov' : 'matroska',
                        '-i', $disk->path($source),
                        '-frames:v', '1', '-an',
                        '-vf', 'scale=min(640\,iw):-2',
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

                    if (! $this->freshFileExists($disk, $source) || ! FileItem::query()->whereKey($file->id)->where('path', $source)->exists()) {
                        return null;
                    }

                    return $disk->move($temporary, $thumbnail) ? $thumbnail : null;
                } catch (ProcessTimedOutException|ProcessStartFailedException) {
                    Cache::put($cacheKey.':failed', true, 300);

                    return null;
                } finally {
                    $disk->delete($temporary);
                }
            });
        } catch (LockTimeoutException) {
            return null;
        }
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
