<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class McpFileContent
{
    public const DISK = 'local';

    /**
     * @return array{disk: string, path: string, original_name: string, mime_type: string, size: int, width: int|null, height: int|null}
     */
    public function storeForTeam(Team $team, string $content, ?string $originalName): array
    {
        $bytes = self::decodeBase64($content);

        if ($bytes === null) {
            throw new \RuntimeException('File content must be valid base64.');
        }

        $mimeType = self::detectMimeType($bytes);
        $extension = $this->extensionForMimeType($mimeType);
        $name = $this->safeOriginalName($originalName, $extension);
        $filename = Str::uuid().'-'.Str::slug(pathinfo($name, PATHINFO_FILENAME)).'.'.$extension;
        $path = 'files/'.$team->id.'/'.$filename;

        Storage::disk(self::DISK)->put($path, $bytes);

        [$width, $height] = self::imageDimensions($bytes);

        return [
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mimeType,
            'size' => strlen($bytes),
            'width' => $width,
            'height' => $height,
        ];
    }

    public static function decodeBase64(string $content): ?string
    {
        $content = trim($content);

        if (str_contains($content, ',')) {
            [$prefix, $payload] = explode(',', $content, 2);

            if (! str_starts_with($prefix, 'data:') || ! str_contains($prefix, ';base64')) {
                return null;
            }

            $content = $payload;
        }

        $decoded = base64_decode($content, true);

        return is_string($decoded) ? $decoded : null;
    }

    public static function detectMimeType(string $bytes): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($bytes) ?: 'application/octet-stream';
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    public static function imageDimensions(string $bytes): array
    {
        $dimensions = @getimagesizefromstring($bytes);

        return is_array($dimensions) ? [$dimensions[0], $dimensions[1]] : [null, null];
    }

    private function safeOriginalName(?string $originalName, string $extension): string
    {
        if ($originalName === null || $originalName === '') {
            return 'file.'.$extension;
        }

        $name = basename($originalName);

        if (! str_contains($name, '.')) {
            return $name.'.'.$extension;
        }

        return $name;
    }

    private function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }
}
