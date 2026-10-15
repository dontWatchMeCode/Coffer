<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Support\FileTypes;
use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveFileRequest extends FormRequest
{
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
            'file.uploaded' => 'The file could not be uploaded. Current server upload limit: '.$this->serverUploadLimit().'.',
            'file.max' => 'The file must be '.FileTypes::MAX_UPLOAD_MEGABYTES.' MB or smaller.',
            'file.mimes' => 'The file must be '.FileTypes::SUPPORTED_FORMATS.'.',
            'file.mimetypes' => 'The file must be '.FileTypes::SUPPORTED_FORMATS.'.',
        ];
    }

    private function serverUploadLimit(): string
    {
        $limit = ini_get('upload_max_filesize');

        return is_string($limit) && $limit !== '' ? $limit : 'unknown';
    }
}
