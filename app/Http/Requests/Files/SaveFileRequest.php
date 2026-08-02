<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveFileRequest extends FormRequest
{
    public const MAX_UPLOAD_KILOBYTES = 102_400;

    public const MAX_UPLOAD_MEGABYTES = 100;

    public const ACCEPTED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public const ACCEPTED_IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('file', $this->isMethod('patch'), includeHttpFileUpload: true);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.uploaded' => 'The image could not be uploaded. Current server upload limit: '.$this->serverUploadLimit().'.',
            'file.max' => 'The image must be '.self::MAX_UPLOAD_MEGABYTES.' MB or smaller.',
            'file.mimes' => 'The image must be a JPEG, PNG, GIF, or WebP file.',
            'file.mimetypes' => 'The image must be a JPEG, PNG, GIF, or WebP file.',
        ];
    }

    private function serverUploadLimit(): string
    {
        $limit = ini_get('upload_max_filesize');

        return is_string($limit) && $limit !== '' ? $limit : 'unknown';
    }
}
