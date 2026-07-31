import type { RecordTag } from './record-tags';

export type SpreadsheetColumnType =
    | 'text'
    | 'number'
    | 'date'
    | 'select'
    | 'checkbox';

export type SpreadsheetCellValue = string | number | boolean | null;

export type SpreadsheetColumn = {
    id: string;
    name: string;
    type: SpreadsheetColumnType;
    width: number;
    hidden: boolean;
    options: string[];
};

export type SpreadsheetRow = {
    id: string;
    cells: Record<string, SpreadsheetCellValue>;
};

export type SpreadsheetSnapshot = {
    version: 1;
    columns: SpreadsheetColumn[];
    rows: SpreadsheetRow[];
};

export type SpreadsheetWorkbook = {
    id: number;
    title: string;
    rowCount: number;
    columnCount: number;
    snapshot?: SpreadsheetSnapshot;
    tags: RecordTag[];
    createdAt: string | null;
    updatedAt: string | null;
    deletedAt?: string | null;
};
