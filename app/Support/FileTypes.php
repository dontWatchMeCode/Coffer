<?php

declare(strict_types=1);

namespace App\Support;

class FileTypes
{
    public const int MAX_UPLOAD_KILOBYTES = 102_400;

    public const int MAX_UPLOAD_MEGABYTES = 100;

    public const int TEXT_PREVIEW_BYTES = 65_536;

    public const string SUPPORTED_FORMATS = 'JPEG, PNG, GIF, WebP, MP4, WebM, PDF, TXT, DOCX, XLSX, or PPTX';

    /** @var array<string, list<string>> */
    private const array EXTENSIONS_BY_MIME_TYPE = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'video/mp4' => ['mp4'],
        'video/webm' => ['webm'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
    ];

    /** @return list<string> */
    public static function mimeTypes(): array
    {
        return array_keys(self::EXTENSIONS_BY_MIME_TYPE);
    }

    /** @return list<string> */
    public static function extensions(): array
    {
        return array_merge(...array_values(self::EXTENSIONS_BY_MIME_TYPE));
    }

    public static function extension(string $mimeType): string
    {
        return self::EXTENSIONS_BY_MIME_TYPE[$mimeType][0] ?? 'bin';
    }

    public static function previewKind(string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/png', 'image/gif', 'image/webp' => 'image',
            'video/mp4', 'video/webm' => 'video',
            'text/plain' => 'text',
            default => null,
        };
    }
}
