<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

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
            'subscription' => ['name', 'price', 'currency', 'billing_cycle', 'next_billing_date', 'url', 'description', 'notes', 'is_active', 'category'],
            'note' => ['title', 'blocks'],
            'collection' => ['title', 'description'],
            'log_entry' => ['body', 'category'],
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
            default => [],
        };
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
            default => [],
        };
    }
}
