import { Badge } from '@/components/ui/badge';
import type { BoxStatus } from './types';

const statusPresentation: Record<
    BoxStatus,
    { label: string; className: string }
> = {
    available: {
        label: 'Tersedia',
        className: 'border-success/30 bg-success/15 text-success',
    },
    partially_allocated: {
        label: 'Dialokasikan sebagian',
        className: 'border-primary/30 bg-primary/15 text-primary',
    },
    fully_allocated: {
        label: 'Terdistribusi penuh',
        className: 'border-border bg-muted text-muted-foreground',
    },
    depleted: {
        label: 'Habis digunakan',
        className: 'border-border bg-muted text-muted-foreground',
    },
};

export function BoxStatusBadge({ status }: { status: BoxStatus }) {
    const presentation = statusPresentation[status];

    return (
        <Badge variant="outline" className={presentation.className}>
            {presentation.label}
        </Badge>
    );
}
