<?php

declare(strict_types=1);

namespace App\Http\Requests\Spreadsheets;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveSpreadsheetWorkbookRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'snapshot' => ['required', 'array'],
            'snapshot.version' => ['required', 'integer', 'in:1'],
            'snapshot.columns' => ['required', 'array', 'min:1', 'max:50'],
            'snapshot.columns.*.id' => ['required', 'string', 'max:100', 'distinct'],
            'snapshot.columns.*.name' => ['required', 'string', 'max:100'],
            'snapshot.columns.*.type' => ['required', Rule::in(['text', 'number', 'date', 'select', 'checkbox'])],
            'snapshot.columns.*.width' => ['required', 'integer', 'min:80', 'max:500'],
            'snapshot.columns.*.hidden' => ['required', 'boolean'],
            'snapshot.columns.*.options' => ['present', 'array', 'max:50'],
            'snapshot.columns.*.options.*' => ['string', 'max:100'],
            'snapshot.rows' => ['required', 'array', 'max:1000'],
            'snapshot.rows.*.id' => ['required', 'string', 'max:100', 'distinct'],
            'snapshot.rows.*.cells' => ['present', 'array', 'max:50'],
            'snapshot.rows.*.cells.*' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_scalar($value) || (is_string($value) && mb_strlen($value) > 10000)) {
                        $fail(sprintf('The %s value is invalid.', $attribute));
                    }
                },
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $snapshot = $this->input('snapshot');

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

                foreach ($snapshot['rows'] ?? [] as $rowIndex => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    if (! is_array($row['cells'] ?? null)) {
                        continue;
                    }

                    foreach (array_keys($row['cells']) as $columnId) {
                        if (! in_array($columnId, $columnIds, true)) {
                            $validator->errors()->add(
                                sprintf('snapshot.rows.%s.cells', $rowIndex),
                                'Cells must reference an existing column.',
                            );
                        }
                    }
                }
            },
        ];
    }
}
