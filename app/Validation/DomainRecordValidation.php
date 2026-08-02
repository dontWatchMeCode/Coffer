<?php

declare(strict_types=1);

namespace App\Validation;

use App\Enums\TaskStatus;
use App\Http\Requests\Files\SaveFileRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DomainRecordValidation
{
    /**
     * @return list<string>
     */
    public static function fieldsFor(string $type): array
    {
        return match ($type) {
            'task' => ['project_id', 'assigned_to', 'title', 'description', 'status', 'progress', 'time_estimate', 'position', 'due_at'],
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
     * @param  array<string, bool>  $requiredWhenPresent
     * @return array<string, mixed>
     */
    public static function rulesFor(string $type, bool $updating, ?Team $team = null, array $data = [], ?Model $model = null, array $requiredWhenPresent = [], bool $includeMcpFileFields = false, bool $includeHttpFileUpload = false, bool $includeNoteBlockIds = false): array
    {
        $required = fn (string $field): array => self::requiredRule($updating, $requiredWhenPresent[$field] ?? true);
        $optional = fn (): array => $updating ? ['sometimes'] : [];

        return match ($type) {
            'task' => [
                'project_id' => [...$required('project_id'), 'integer', Rule::exists('projects', 'id')->where(fn ($query) => $query->where('team_id', $team?->id))],
                'assigned_to' => [...$optional(), 'nullable', 'integer', Rule::exists('team_members', 'user_id')->where(fn ($query) => $query->where('team_id', $team?->id))],
                'title' => [...$required('title'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
                'status' => [...$required('status'), Rule::in(self::taskStatusValues($team, $data, $model))],
                'progress' => [...$optional(), 'integer', 'between:0,100'],
                'time_estimate' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'position' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'due_at' => [...$optional(), 'nullable', 'date'],
            ],
            'calendar_event' => [
                'title' => [...$required('title'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
                'date' => [...$required('date'), 'date'],
                'time' => [...$optional(), 'nullable', 'date_format:H:i'],
            ],
            'contact' => [
                'name' => [...$required('name'), 'string', 'max:255'],
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
                'title' => [...$required('title'), 'string', 'max:255'],
                'url' => [...$required('url'), 'string', 'url', 'max:2048'],
                'description' => [...$optional(), 'nullable', 'string', 'max:500'],
                'notes' => [...$optional(), 'nullable', 'string'],
            ],
            'subscription' => [
                'name' => [...$required('name'), 'string', 'max:255'],
                'price' => [...$optional(), 'nullable', 'numeric', 'min:0', 'max:999999.99'],
                'currency' => [...$optional(), 'nullable', 'string', 'max:3'],
                'billing_cycle' => [...$optional(), 'nullable', 'string', Rule::in(['weekly', 'monthly', 'yearly'])],
                'next_billing_date' => [...$optional(), 'nullable', 'date'],
                'first_billing_date' => [...$optional(), 'nullable', 'date'],
                'url' => [...$optional(), 'nullable', 'string', 'url', 'max:2048'],
                'description' => [...$optional(), 'nullable', 'string', 'max:500'],
                'notes' => [...$optional(), 'nullable', 'string'],
                'is_active' => $updating ? ['sometimes', 'boolean'] : ['nullable', 'boolean'],
                'category' => [...$optional(), 'nullable', 'string', 'max:100'],
            ],
            'note' => self::noteRules($required, $includeNoteBlockIds),
            'collection' => [
                'title' => [...$required('title'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
            ],
            'log_entry' => [
                'body' => [...$required('body'), 'string', 'max:5000'],
                'category' => [...$optional(), 'nullable', 'string', 'max:80'],
            ],
            'file' => self::fileRules($updating, $required, $optional, $includeMcpFileFields, $includeHttpFileUpload),
            default => [],
        };
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

    /**
     * @return list<string>
     */
    private static function requiredRule(bool $updating, bool $requiredWhenPresent): array
    {
        if (! $updating) {
            return ['required'];
        }

        return $requiredWhenPresent ? ['sometimes', 'required'] : ['sometimes'];
    }

    /**
     * @param  callable(string): list<string>  $required
     * @return array<string, mixed>
     */
    private static function noteRules(callable $required, bool $includeBlockIds): array
    {
        $rules = [
            'title' => [...$required('title'), 'string', 'max:255'],
            'blocks' => ['sometimes', 'nullable', 'array', 'max:50'],
            'blocks.*.type' => ['required', 'string', Rule::in(['text', 'excalidraw', 'mermaid'])],
            'blocks.*.position' => ['required', 'integer', 'min:0'],
            'blocks.*.payload' => ['sometimes', 'nullable', 'array'],
        ];

        if ($includeBlockIds) {
            $rules['blocks.*.id'] = ['sometimes', 'nullable', 'integer'];
        }

        return $rules;
    }

    /**
     * @param  callable(string): list<string>  $required
     * @param  callable(): list<string>  $optional
     * @return array<string, mixed>
     */
    private static function fileRules(bool $updating, callable $required, callable $optional, bool $includeMcpFileFields, bool $includeHttpFileUpload): array
    {
        $rules = [
            'title' => [...$required('title'), 'string', 'max:255'],
            'description' => [...$optional(), 'nullable', 'string'],
        ];

        if ($includeHttpFileUpload) {
            $rules['file'] = $updating
                ? ['prohibited']
                : [
                    'required',
                    'file',
                    'max:'.SaveFileRequest::MAX_UPLOAD_KILOBYTES,
                    'mimes:'.implode(',', SaveFileRequest::ACCEPTED_IMAGE_EXTENSIONS),
                    'mimetypes:'.implode(',', SaveFileRequest::ACCEPTED_IMAGE_MIME_TYPES),
                ];
        }

        if ($includeMcpFileFields) {
            return array_merge($rules, [
                'description' => [...$optional(), 'nullable', 'string', 'max:5000'],
                'original_name' => [...$optional(), 'nullable', 'string', 'max:255'],
                'mime_type' => [...$optional(), 'nullable', 'string', 'max:127'],
                'size' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'width' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'height' => [...$optional(), 'nullable', 'integer', 'min:0'],
                'content' => [...$optional(), 'nullable', 'string', 'max:'.(int) ceil(SaveFileRequest::MAX_UPLOAD_KILOBYTES * 1024 * 4 / 3 + 500)],
            ]);
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function taskStatusValues(?Team $team, array $data, ?Model $model): array
    {
        $projectId = $data['project_id'] ?? null;

        if ($projectId === null && $model instanceof Task) {
            $projectId = $model->project_id;
        }

        if (! $team instanceof Team) {
            return TaskStatus::values();
        }

        return Project::taskStatusValuesFor($team, $projectId);
    }
}
