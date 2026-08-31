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
    in_progress: {
        label: 'Sedang diperiksa',
        className: 'bg-primary/10 text-primary',
    },
    discrepancy: {
        label: 'Ada selisih',
        className: 'bg-destructive/10 text-destructive',
    },
    completed: {
        label: 'Tahap 1 lulus',
        className: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
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
