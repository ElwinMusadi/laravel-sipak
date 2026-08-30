import { Badge } from '@/components/ui/badge';

export type BapStatus = 'draft' | 'submitted' | 'waiting_verification';

const statusPresentation: Record<
    BapStatus,
    { label: string; className: string }
> = {
    draft: {
        label: 'Draft',
        className: 'border-border bg-muted text-muted-foreground',
    },
    submitted: {
        label: 'Menunggu verifikasi',
        className: 'border-primary/30 bg-primary/15 text-primary',
    },
    waiting_verification: {
        label: 'Menunggu verifikasi',
        className: 'border-primary/30 bg-primary/15 text-primary',
    },
};

export function BapStatusBadge({ status }: { status: BapStatus }) {
    const presentation = statusPresentation[status];

    return (
        <Badge variant="outline" className={presentation.className}>
            {presentation.label}
        </Badge>
    );
}
