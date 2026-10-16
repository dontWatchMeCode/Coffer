<?php

declare(strict_types=1);

namespace App\Validation;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Support\FileTypes;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'project' => ['name', 'description', 'archived', 'status_options'],
            'spreadsheet' => ['title', 'snapshot'],
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
            'project' => ['name'],
            'spreadsheet' => ['title'],
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
            'project' => [
                'status_options' => 'May be omitted on create (defaults to the team task statuses). Names are unique within the team.',
            ],
            'spreadsheet' => [
                'snapshot' => 'Full workbook snapshot {version, columns, rows}. May be omitted on create (defaults to a blank workbook). Returned by records.get; records.search returns column_count/row_count instead of the full snapshot.',
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
        $optional = fn (): array => $updating ? [Rule::sometimes()] : [];

        return match ($type) {
            'task' => [
                'project_id' => [...$required('project_id'), 'integer', Rule::exists('projects', 'id')->where(fn ($query) => $query->where('team_id', $team?->id))],
                'assigned_to' => [...$optional(), Rule::nullable(), 'integer', Rule::exists('team_members', 'user_id')->where(fn ($query) => $query->where('team_id', $team?->id))],
                'title' => [...$required('title'), Rule::string(), 'max:255'],
                'description' => [...$optional(), Rule::nullable(), Rule::string()],
                'status' => [...$required('status'), Rule::in(self::taskStatusValues($team, $data, $model))],
                'progress' => [...$optional(), 'integer', 'between:0,100'],
                'time_estimate' => [...$optional(), Rule::nullable(), 'integer', 'min:0'],
                'position' => [...$optional(), Rule::nullable(), 'integer', 'min:0'],
                'due_at' => [...$optional(), Rule::nullable(), Rule::date()],
            ],
            'calendar_event' => [
                'title' => [...$required('title'), Rule::string(), 'max:255'],
                'description' => [...$optional(), Rule::nullable(), Rule::string()],
                'date' => [...$required('date'), Rule::date()],
                'time' => [...$optional(), Rule::nullable(), Rule::date()->format('H:i')],
            ],
            'contact' => [
                'name' => [...$required('name'), Rule::string(), 'max:255'],
                'phone_numbers' => [...$optional(), Rule::nullable(), Rule::array(), 'max:20'],
                'phone_numbers.*.label' => [Rule::nullable(), Rule::string(), 'max:100'],
                'phone_numbers.*.value' => [Rule::required(), Rule::string(), 'max:255'],
                'email_addresses' => [...$optional(), Rule::nullable(), Rule::array(), 'max:20'],
                'email_addresses.*.label' => [Rule::nullable(), Rule::string(), 'max:100'],
                'email_addresses.*.value' => [Rule::required(), Rule::email(), 'max:255'],
                'links' => [...$optional(), Rule::nullable(), Rule::array(), 'max:20'],
                'links.*.label' => [Rule::nullable(), Rule::string(), 'max:100'],
                'links.*.value' => [Rule::required(), Rule::url(), 'max:2048'],
                'address' => [...$optional(), Rule::nullable(), Rule::string()],
                'additional_info' => [...$optional(), Rule::nullable(), Rule::string()],
            ],
            'bookmark' => [
                'title' => [...$required('title'), Rule::string(), 'max:255'],
                'url' => [...$required('url'), Rule::string(), Rule::url(), 'max:2048'],
                'description' => [...$optional(), Rule::nullable(), Rule::string(), 'max:500'],
                'notes' => [...$optional(), Rule::nullable(), Rule::string()],
            ],
            'subscription' => [
                'name' => [...$required('name'), Rule::string(), 'max:255'],
                'price' => [...$optional(), Rule::nullable(), Rule::numeric(), 'min:0', 'max:999999.99'],
                'currency' => [...$optional(), Rule::nullable(), Rule::string(), 'max:3'],
                'billing_cycle' => [...$optional(), Rule::nullable(), Rule::string(), Rule::in(['weekly', 'monthly', 'yearly'])],
                'next_billing_date' => [...$optional(), Rule::nullable(), Rule::date()],
                'first_billing_date' => [...$optional(), Rule::nullable(), Rule::date()],
                'url' => [...$optional(), Rule::nullable(), Rule::string(), Rule::url(), 'max:2048'],
                'description' => [...$optional(), Rule::nullable(), Rule::string(), 'max:500'],
                'notes' => [...$optional(), Rule::nullable(), Rule::string()],
                'is_active' => $updating ? [Rule::sometimes(), Rule::boolean()] : [Rule::nullable(), Rule::boolean()],
                'category' => [...$optional(), Rule::nullable(), Rule::string(), 'max:100'],
            ],
            'note' => self::noteRules($required, $includeNoteBlockIds),
            'collection' => [
                'title' => [...$required('title'), Rule::string(), 'max:255'],
                'description' => [...$optional(), Rule::nullable(), Rule::string()],
            ],
            'log_entry' => [
                'body' => [...$required('body'), Rule::string(), 'max:5000'],
                'category' => [...$optional(), Rule::nullable(), Rule::string(), 'max:80'],
            ],
            'file' => self::fileRules($updating, $required, $optional, $includeMcpFileFields, $includeHttpFileUpload),
            'project' => [
                'name' => [...$required('name'), Rule::string(), 'max:255', Rule::unique('projects', 'name')->where(fn ($query) => $query->where('team_id', $team?->id))->ignore($model?->getKey())],
                'description' => [...$optional(), Rule::nullable(), Rule::string()],
                'archived' => [...$optional(), Rule::nullable(), Rule::boolean()],
                'status_options' => [...$optional(), Rule::nullable(), Rule::array()],
                'status_options.*.value' => [Rule::required(), Rule::string(), Rule::regex('/^[a-z0-9_]+$/'), Rule::distinct()],
                'status_options.*.label' => [Rule::required(), Rule::string(), 'max:40'],
            ],
            'spreadsheet' => self::spreadsheetRules($required, $data),
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
            return [Rule::required()];
        }

        return $requiredWhenPresent ? [Rule::sometimes(), Rule::required()] : [Rule::sometimes()];
    }

    /**
     * @param  callable(string): list<string>  $required
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function spreadsheetRules(callable $required, array $data): array
    {
        $rules = [
            'title' => [...$required('title'), Rule::string(), 'max:255'],
            'snapshot' => [Rule::sometimes(), Rule::array()],
        ];

        $snapshotPresent = array_key_exists('snapshot', $data) && is_array($data['snapshot']);

        if ($snapshotPresent) {
            $rules += [
                'snapshot.version' => [Rule::required(), 'integer', Rule::in([1])],
                'snapshot.columns' => [Rule::required(), Rule::array(), 'min:1', 'max:50'],
                'snapshot.columns.*.id' => [Rule::required(), Rule::string(), 'max:100', Rule::distinct()],
                'snapshot.columns.*.name' => [Rule::required(), Rule::string(), 'max:100'],
                'snapshot.columns.*.type' => [Rule::required(), Rule::in(['text', 'number', 'date', 'select', 'checkbox'])],
                'snapshot.columns.*.width' => [Rule::required(), 'integer', 'min:80', 'max:500'],
                'snapshot.columns.*.hidden' => [Rule::required(), Rule::boolean()],
                'snapshot.columns.*.options' => [Rule::present(), Rule::array(), 'max:50'],
                'snapshot.columns.*.options.*' => [Rule::string(), 'max:100'],
                'snapshot.rows' => [Rule::required(), Rule::array(), 'max:1000'],
                'snapshot.rows.*.id' => [Rule::required(), Rule::string(), 'max:100', Rule::distinct()],
                'snapshot.rows.*.cells' => [Rule::present(), Rule::array(), 'max:50'],
                'snapshot.rows.*.cells.*' => [
                    Rule::nullable(),
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if (! is_scalar($value) || (is_string($value) && mb_strlen($value) > 10000)) {
                            $fail(sprintf('The %s value is invalid.', $attribute));
                        }
                    },
                ],
            ];
        }

        return $rules;
    }

    /**
     * Shared RTE block rules used by task comments and notes.
     *
     * @return array<string, mixed>
     */
    public static function rteBlockRules(bool $requireBlocks, bool $includeIds, bool $withContentCap = true): array
    {
        $rules = [
            'blocks' => $requireBlocks
                ? [Rule::required(), Rule::array(), 'min:1', 'max:50']
                : [Rule::sometimes(), Rule::nullable(), Rule::array(), 'max:50'],
            'blocks.*.type' => [Rule::required(), Rule::string(), Rule::in(['text', 'excalidraw', 'mermaid'])],
            'blocks.*.position' => [Rule::required(), 'integer', 'min:0'],
            'blocks.*.payload' => [Rule::sometimes(), Rule::nullable(), Rule::array()],
        ];

        if ($withContentCap) {
            $rules['blocks.*.payload.content'] = [Rule::sometimes(), Rule::nullable(), Rule::string(), 'max:10000'];
        }

        if ($includeIds) {
            $rules['blocks.*.id'] = [Rule::sometimes(), Rule::nullable(), 'integer'];
        }

        return $rules;
    }

    /**
     * Conditional billing-date ordering shared by web and MCP subscription validation.
     */
    public static function applySubscriptionRules(Validator $validator): void
    {
        $validator->sometimes('first_billing_date', [Rule::date()->before('next_billing_date')], fn ($input): bool => $input->first_billing_date !== null
            && $input->next_billing_date !== null);
    }

    /**
     * @param  callable(string): list<string>  $required
     * @return array<string, mixed>
     */
    private static function noteRules(callable $required, bool $includeBlockIds): array
    {
        return [
            'title' => [...$required('title'), Rule::string(), 'max:255'],
            ...self::rteBlockRules(false, $includeBlockIds, false),
        ];
    }

    /**
     * Shared spreadsheet snapshot integrity checks (size cap + cell/column references).
     */
    public static function assertSpreadsheetSnapshot(Validator $validator, mixed $snapshot): void
    {
        if (! is_array($snapshot)) {
            return;
        }

        $encodedSnapshot = json_encode($snapshot);

        if ($encodedSnapshot === false || strlen($encodedSnapshot) > 2_000_000) {
            $validator->errors()->add(
                'snapshot',
                'The spreadsheet may not exceed 2 MB.',
            );

            return;
        }

        $columns = $snapshot['columns'] ?? [];

        if (! is_array($columns)) {
            return;
        }

        $columnIds = [];

        foreach ($columns as $column) {
            if (is_array($column) && is_string($column['id'] ?? null)) {
                $columnIds[] = $column['id'];
            }
        }

        $rows = $snapshot['rows'] ?? [];

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $rowIndex => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! is_array($row['cells'] ?? null)) {
                continue;
            }

            foreach (array_keys($row['cells']) as $columnId) {
                if (! in_array((string) $columnId, $columnIds, true)) {
                    $validator->errors()->add(
                        sprintf('snapshot.rows.%s.cells', $rowIndex),
                        'Cells must reference an existing column.',
                    );

                    break;
                }
            }
        }
    }

    /**
     * @param  callable(string): list<string>  $required
     * @param  callable(): list<string>  $optional
     * @return array<string, mixed>
     */
    private static function fileRules(bool $updating, callable $required, callable $optional, bool $includeMcpFileFields, bool $includeHttpFileUpload): array
    {
        $rules = [
            'title' => [...$required('title'), Rule::string(), 'max:255'],
            'description' => [...$optional(), Rule::nullable(), Rule::string()],
        ];

        if ($includeHttpFileUpload) {
            $rules['file'] = $updating
                ? [Rule::prohibited()]
                : [
                    Rule::required(),
                    Rule::file()->max(FileTypes::MAX_UPLOAD_KILOBYTES),
                    'mimes:'.implode(',', FileTypes::extensions()),
                    'mimetypes:'.implode(',', FileTypes::mimeTypes()),
                ];
        }

        if ($includeMcpFileFields) {
            return array_merge($rules, [
                'description' => [...$optional(), Rule::nullable(), Rule::string(), 'max:5000'],
                'original_name' => [...$optional(), Rule::nullable(), Rule::string(), 'max:255'],
                'mime_type' => [...$optional(), Rule::nullable(), Rule::string(), 'max:127'],
                'size' => [...$optional(), Rule::nullable(), 'integer', 'min:0'],
                'width' => [...$optional(), Rule::nullable(), 'integer', 'min:0'],
                'height' => [...$optional(), Rule::nullable(), 'integer', 'min:0'],
                'content' => [...$optional(), Rule::nullable(), Rule::string(), 'max:'.(int) ceil(FileTypes::MAX_UPLOAD_KILOBYTES * 1024 * 4 / 3 + 500)],
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
