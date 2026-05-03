import type { ContactEntry } from '@/types';

export function emptyEntry(): ContactEntry {
    return { label: '', value: '' };
}

export function firstEntryValueError(
    errors: Record<string, unknown>,
    field: string,
): string | undefined {
    const message = Object.entries(errors).find(([key]) =>
        new RegExp(`^${field}\\.\\d+\\.value$`).test(key),
    )?.[1];

    return typeof message === 'string' ? message : undefined;
}
