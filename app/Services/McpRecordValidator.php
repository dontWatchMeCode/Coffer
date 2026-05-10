<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Team;
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
            'note' => ['title', 'body', 'format', 'drawing_data'],
            'collection' => ['title', 'description'],
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
                'format' => 'Valid values are "text" and "excalidraw". Use "text" for Markdown-backed rich text notes; do not use "markdown".',
                'body' => 'Markdown content for text notes.',
                'drawing_data' => 'Excalidraw scene data for excalidraw notes.',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesFor(string $type, bool $updating, Team $team): array
    {
        $required = fn (string $rule): array => $updating ? ['sometimes', $rule] : [$rule];
        $optional = fn (): array => $updating ? ['sometimes'] : [];

        return match ($type) {
            'task' => [
                'project_id' => [...$required('required'), 'integer', Rule::exists('projects', 'id')->where(fn ($query) => $query->where('team_id', $team->id))],
                'assigned_to' => [...$optional(), 'nullable', 'integer', Rule::exists('team_members', 'user_id')->where(fn ($query) => $query->where('team_id', $team->id))],
                'title' => [...$required('required'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
                'status' => [...$required('required'), Rule::enum(TaskStatus::class)],
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
                'body' => [...$optional(), 'nullable', 'string'],
                'format' => ['sometimes', 'required', 'string', Rule::in(['text', 'excalidraw'])],
                'drawing_data' => [...$optional(), 'nullable', 'array'],
            ],
            'collection' => [
                'title' => [...$required('required'), 'string', 'max:255'],
                'description' => [...$optional(), 'nullable', 'string'],
            ],
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
                'format.in' => 'The selected format is invalid. Use "text" for Markdown-backed rich text notes or "excalidraw" for drawing notes.',
            ],
            default => [],
        };
    }
}
