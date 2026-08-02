<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Files\SaveFileRequest;
use App\Models\Team;
use App\Validation\DomainRecordValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class McpRecordValidator
{
    /**
     * @return list<string>
     */
    public static function fieldsFor(string $type): array
    {
        return DomainRecordValidation::fieldsFor($type);
    }

    /**
     * @return list<string>
     */
    public static function requiredFieldsFor(string $type): array
    {
        return DomainRecordValidation::requiredFieldsFor($type);
    }

    /**
     * @return array<string, string>
     */
    public static function fieldNotesFor(string $type): array
    {
        return DomainRecordValidation::fieldNotesFor($type);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function rulesFor(string $type, bool $updating, Team $team, array $data = [], ?Model $model = null): array
    {
        return DomainRecordValidation::rulesFor($type, $updating, $team, $data, $model, includeMcpFileFields: true);
    }

    public static function applyConditionalRules(Validator $validator, string $type): void
    {
        if ($type === 'subscription') {
            $validator->sometimes('first_billing_date', 'before:next_billing_date', fn ($input): bool => $input->first_billing_date !== null
                && $input->next_billing_date !== null);
        }

        if ($type === 'file') {
            $validator->after(function (Validator $validator): void {
                $data = $validator->getData();

                if (! isset($data['content']) || $data['content'] === '') {
                    return;
                }

                $encodedMaxLen = (int) ceil(SaveFileRequest::MAX_UPLOAD_KILOBYTES * 1024 * 4 / 3 + 500);

                if (strlen((string) $data['content']) > $encodedMaxLen) {
                    $validator->errors()->add('content', 'The file must be 100 MB or smaller.');

                    return;
                }

                $decoded = McpFileContent::decodeBase64($data['content']);

                if ($decoded === null) {
                    $validator->errors()->add('content', 'File content must be valid base64 or data URI.');

                    return;
                }

                if (strlen($decoded) > SaveFileRequest::MAX_UPLOAD_KILOBYTES * 1024) {
                    $validator->errors()->add('content', 'The file must be 100 MB or smaller.');

                    return;
                }

                $mimeType = McpFileContent::detectMimeType($decoded);

                if (! in_array($mimeType, SaveFileRequest::ACCEPTED_IMAGE_MIME_TYPES, true)) {
                    $validator->errors()->add('content', 'The file must be a JPEG, PNG, GIF, or WebP file.');
                }
            });
        }
    }

    /**
     * @return array<string, string>
     */
    public static function messagesFor(string $type): array
    {
        return DomainRecordValidation::messagesFor($type);
    }
}
