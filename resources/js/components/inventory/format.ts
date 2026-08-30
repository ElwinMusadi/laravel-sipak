export function formatNomeratur(value: number): string {
    return value.toString().padStart(7, '0');
}

export function formatRange(start: number, end: number): string {
    return `${formatNomeratur(start)}–${formatNomeratur(end)}`;
}

export function formatQuantity(value: number): string {
    return new Intl.NumberFormat('id-ID').format(value);
}

export function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(value));
}

export function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
