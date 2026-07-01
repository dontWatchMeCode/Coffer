<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\Files\SaveFileRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class McpRecordValidator
{
    /**
     * @return list<string>
     */
    public static function fieldsFor(string $type): array
    {
        return match ($type) {
            'task' => ['project_id', 'assigned_to', 'title', 'description', 'status', 'progress', 'position', 'due_at'],
            'calendar_event' => ['title', 'description', 'date', 'time'],
            'contact' => ['name', 'phone_numbers', 'email_addresses', 'links', 'address', 'additional_info'],
            'bookmark' => ['title', 'url', 'description', 'notes'],
            'subscription' => ['name', 'price', 'currency', 'billing_cycle', 'first_billing_date', 'next_billing_date', 'url', 'description', 'notes', 'is_active', 'category'],
            'note' => ['title', 'blocks'],
            'collection' => ['title', 'description'],
            'log_entry' => ['body', 'category'],
            'file' => ['title', 'description', 'original_name', 'mime_type', 'size', 'width', 'height', 'content'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function requiredFieldsFor(string $type): array
    {
        return match ($type) {
            'task' => ['project_id', 'title', 'status'],
            'calendar_event' => ['title', 'date'],
            'contact' => ['name'],
            'bookmark' => ['title', 'url'],
            'subscription' => ['name'],
            'note' => ['title'],
            'collection' => ['title'],
            'log_entry' => ['body'],
            'file' => ['title'],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function fieldNotesFor(string $type): array
    {
        return match ($type) {
            'note' => [
                'blocks' => 'Array of blocks. Each block: {type: "text"|"excalidraw"|"mermaid", position: int, payload: {content: "markdown"} or {scene: {...}} or {content: "mermaid code"}}.',
            ],
            'file' => [
                'content' => 'Optional base64-encoded file bytes. Data URI format is also accepted.',
                'original_name' => 'Used as display name and filename seed when content is provided.',
                'size' => 'Auto-populated from decoded bytes when content is provided.',
                'mime_type' => 'Auto-detected from decoded bytes when content is provided.',
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function rulesFor(string $type, bool $updating, Team $team, array $data = [], ?Model $model = null): array
    {
        $required = fn (string $rule): array => $updating ? ['sometimes', $rule] : [$rule];
        $optional = fn (): array => $updating ? ['sometimes'] : [];

        return match ($type) {
            'task' => [
                'project_id' => [...$required('required'), 'integer', Rule::exists('projects', 'id')->where(fn ($query) => $query->where('team_id', $team->id))],
                'assigned_to' => [...$optional(), 'nullable', 'integer', Rule::exists('team_members', 'user_id')->where(fn ($query) => $query->where('team_id', $team->id))],
                'title' => [...$required('required'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
                'status' => [...$required('required'), Rule::in(self::taskStatusValues($team, $data, $model))],
                'progress' => [...$optional(), 'integer', 'between:0,100'],
                'position' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'due_at' => [...$optional(), 'nullable', 'date'],
            ],
            'calendar_event' => [
                'title' => [...$required('required'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
                'date' => [...$required('required'), 'date'],
                'time' => [...$optional(), 'nullable', 'date_format:H:i'],
            ],
            'contact' => [
                'name' => [...$required('required'), 'string', 'max:255'],
                'phone_numbers' => [...$optional(), 'nullable', 'array', 'max:20'],
                'phone_numbers.*.label' => ['nullable', 'string', 'max:100'],
                'phone_numbers.*.value' => ['required', 'string', 'max:255'],
                'email_addresses' => [...$optional(), 'nullable', 'array', 'max:20'],
                'email_addresses.*.label' => ['nullable', 'string', 'max:100'],
                'email_addresses.*.value' => ['required', 'email', 'max:255'],
                'links' => [...$optional(), 'nullable', 'array', 'max:20'],
                'links.*.label' => ['nullable', 'string', 'max:100'],
                'links.*.value' => ['required', 'url', 'max:2048'],
                'address' => [...$optional(), 'nullable', 'string'],
                'additional_info' => [...$optional(), 'nullable', 'string'],
            ],
            'bookmark' => [
                'title' => [...$required('required'), 'string', 'max:255'],
                'url' => [...$required('required'), 'string', 'url', 'max:2048'],
                'description' => [...$optional(), 'nullable', 'string', 'max:500'],
                'notes' => [...$optional(), 'nullable', 'string'],
            ],
            'subscription' => [
                'name' => [...$required('required'), 'string', 'max:255'],
                'price' => [...$optional(), 'nullable', 'numeric', 'min:0', 'max:999999.99'],
                'currency' => [...$optional(), 'nullable', 'string', 'max:3'],
                'billing_cycle' => [...$optional(), 'nullable', 'string', Rule::in(['weekly', 'monthly', 'yearly'])],
                'next_billing_date' => [...$optional(), 'nullable', 'date'],
                'first_billing_date' => [...$optional(), 'nullable', 'date'],
                'url' => [...$optional(), 'nullable', 'string', 'url', 'max:2048'],
                'description' => [...$optional(), 'nullable', 'string', 'max:500'],
                'notes' => [...$optional(), 'nullable', 'string'],
                'is_active' => [...$optional(), 'boolean'],
                'category' => [...$optional(), 'nullable', 'string', 'max:100'],
            ],
            'note' => [
                'title' => [...$required('required'), 'string', 'max:255'],
                'blocks' => [...$optional(), 'nullable', 'array', 'max:50'],
                'blocks.*.type' => ['required', 'string', Rule::in(['text', 'excalidraw', 'mermaid'])],
                'blocks.*.position' => ['required', 'integer', 'min:0'],
                'blocks.*.payload' => ['sometimes', 'nullable', 'array'],
            ],
            'collection' => [
                'title' => [...$required('required'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
            ],
            'log_entry' => [
                'body' => [...$required('required'), 'string', 'max:5000'],
                'category' => [...$optional(), 'nullable', 'string', 'max:80'],
            ],
            'file' => [
                'title' => [...$required('required'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string', 'max:5000'],
                'original_name' => [...$optional(), 'nullable', 'string', 'max:255'],
                'mime_type' => [...$optional(), 'nullable', 'string', 'max:127'],
                'size' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'width' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'height' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'content' => [...$optional(), 'nullable', 'string', 'max:'.(int) ceil(SaveFileRequest::MAX_UPLOAD_KILOBYTES * 1024 * 4 / 3 + 500)],
            ],
            default => [],
        };
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
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function taskStatusValues(Team $team, array $data, ?Model $model): array
    {
        $projectId = $data['project_id'] ?? null;

        if ($projectId === null && $model instanceof Task) {
            $projectId = $model->project_id;
        }

        if ($projectId === null) {
            return Project::taskStatusValuesFor($team, null);
        }

        return Project::taskStatusValuesFor($team, $projectId);
    }

    /**
     * @return array<string, string>
     */
    public static function messagesFor(string $type): array
    {
        return match ($type) {
            'note' => [
                'blocks.*.type.in' => 'Block type must be "text", "excalidraw", or "mermaid".',
            ],
            'file' => [
                'size.min' => 'File size must be a non-negative integer.',
                'content.string' => 'File content must be a valid string.',
            ],
            default => [],
        };
    }
}
