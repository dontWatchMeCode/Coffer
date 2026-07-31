import type {
    SpreadsheetCellValue,
    SpreadsheetSnapshot,
} from '@/types/spreadsheets';

export function cloneSpreadsheetSnapshot(
    snapshot: SpreadsheetSnapshot,
): SpreadsheetSnapshot {
    return JSON.parse(JSON.stringify(snapshot)) as SpreadsheetSnapshot;
}

export function withSpreadsheetCell(
    snapshot: SpreadsheetSnapshot,
    rowId: string,
    columnId: string,
    value: SpreadsheetCellValue,
): SpreadsheetSnapshot {
    const rowIndex = snapshot.rows.findIndex((item) => item.id === rowId);

    if (rowIndex < 0) {
        return snapshot;
    }

    const rows = [...snapshot.rows];
    const row = rows[rowIndex];

    rows[rowIndex] = {
        ...row,
        cells: {
            ...row.cells,
            [columnId]: value,
        },
    };

    return { ...snapshot, rows };
}
