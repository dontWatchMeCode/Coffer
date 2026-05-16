export function drawingToggleBtnClass(active: boolean): string {
    return active
        ? 'border-primary bg-primary text-primary-foreground'
        : 'border-border bg-background text-muted-foreground hover:bg-muted/50 hover:text-foreground';
}
