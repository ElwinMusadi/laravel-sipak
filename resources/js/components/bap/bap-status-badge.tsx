import { Badge } from '@/components/ui/badge';

export type BapStatus =
    | 'draft'
    | 'submitted'
    | 'under_verification'
    | 'needs_clarification'
    | 'waiting_verification_phase_2';

const statusPresentation: Record<
    BapStatus,
    { label: string; className: string }
> = {
    draft: {
        label: 'Draft',
        className: 'border-border bg-muted text-muted-foreground',
    },
    submitted: {
        label: 'Menunggu Verifikasi Tahap 1',
        className: 'border-primary/30 bg-primary/15 text-primary',
    },
    under_verification: {
        label: 'Sedang Diverifikasi Tahap 1',
        className: 'border-primary/30 bg-primary/15 text-primary',
    },
    needs_clarification: {
        label: 'Perlu Klarifikasi',
        className: 'border-destructive/30 bg-destructive/10 text-destructive',
    },
    waiting_verification_phase_2: {
        label: 'Menunggu Verifikasi Tahap 2',
        className:
            'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
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
