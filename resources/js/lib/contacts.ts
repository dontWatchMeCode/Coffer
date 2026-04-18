import type { ContactEntry } from '@/types';

export function emptyEntry(): ContactEntry {
    return { label: '', value: '' };
}
