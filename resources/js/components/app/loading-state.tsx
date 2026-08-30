import { Spinner } from '@/components/ui/spinner';

type Props = {
    label?: string;
};

export function LoadingState({ label = 'Memuat data…' }: Props) {
    return (
        <div
            className="flex min-h-48 flex-col items-center justify-center gap-3 px-6 py-8 text-center"
            aria-live="polite"
        >
            <Spinner className="text-primary size-5" aria-label={label} />
            <p className="text-muted-foreground text-sm">{label}</p>
        </div>
    );
}
