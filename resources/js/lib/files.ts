export function titleFromFileName(fileName: string): string {
    const trimmedName = fileName.trim();
    const extensionIndex = trimmedName.lastIndexOf('.');

    if (extensionIndex <= 0) {
        return trimmedName;
    }

    return trimmedName.slice(0, extensionIndex);
}
