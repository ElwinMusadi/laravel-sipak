import { Badge } from '@/components/ui/badge';
import type { DashboardStatus } from './dashboard-data';

const statusPresentation: Record<
    DashboardStatus,
    { label: string; className: string }
> = {
    draft: {
        label: 'Draft',
        className: 'bg-secondary text-secondary-foreground',
    },
    waiting: {
        label: 'Menunggu',
        className: 'bg-primary/10 text-primary',
    },
};

export function StatusBadge({ status }: { status: DashboardStatus }) {
    const presentation = statusPresentation[status];

    return (
        <Badge variant="outline" className={presentation.className}>
            {presentation.label}
        </Badge>
    );
}
