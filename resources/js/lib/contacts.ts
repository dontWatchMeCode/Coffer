import type { ContactEntry } from '@/types';

type ContactEntryField = 'email_addresses' | 'links' | 'phone_numbers';

const maxContactEntries = 20;

export function emptyEntry(): ContactEntry {
    return { label: '', value: '' };
}

export function firstEntryValueError(
    errors: Record<string, unknown>,
    field: ContactEntryField,
): string | undefined {
    // Server validation allows up to 20 rows per contact entry group.
    for (let index = 0; index < maxContactEntries; index += 1) {
        const message = errors[`${field}.${index}.value`];

        if (typeof message === 'string') {
            return message;
        }
    }

    return undefined;
}
