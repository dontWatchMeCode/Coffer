import type {
    SpreadsheetCellValue,
    SpreadsheetColumn,
    SpreadsheetRow,
} from '@/types/spreadsheets';

export function spreadsheetToCsv(
    columns: SpreadsheetColumn[],
    rows: SpreadsheetRow[],
): string {
    return [
        columns.map((column) => column.name),
        ...rows.map((row) =>
            columns.map((column) => row.cells[column.id] ?? ''),
        ),
    ]
        .map((row) => row.map(csvValue).join(','))
        .join('\r\n');
}

export function downloadSpreadsheetCsv(
    filename: string,
    columns: SpreadsheetColumn[],
    rows: SpreadsheetRow[],
): void {
    const blob = new Blob([`\ufeff${spreadsheetToCsv(columns, rows)}`], {
        type: 'text/csv;charset=utf-8',
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `${slugify(filename) || 'spreadsheet'}.csv`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 0);
}

function csvValue(value: SpreadsheetCellValue | ''): string {
    const normalized =
        typeof value === 'boolean' ? (value ? 'Yes' : 'No') : value;
    let text = String(normalized ?? '');

    if (
        typeof normalized === 'string' &&
        (/^[\t\r]/.test(normalized) || /^[\t\r ]*[=+\-@]/.test(normalized))
    ) {
        text = `'${text}`;
    }

    return /[",\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}
