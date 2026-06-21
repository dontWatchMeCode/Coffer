export function formatChartMonth(value: number | Date | string): string {
    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        year: '2-digit',
    });
}
