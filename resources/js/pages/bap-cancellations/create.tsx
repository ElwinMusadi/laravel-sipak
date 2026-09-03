import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/bap-cancellations';

/**
 * Retained only so historical frontend bundles from before unified BAP remain resolvable.
 * New cancellation details are entered with the parent BAP draft.
 */
export default function CreateBapCancellation() {
    return (
        <>
            <Head title="BAP Batal/Rusak" />
            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Button variant="outline" asChild>
                    <Link href={index()}>
                        <ArrowLeft data-icon="inline-start" />
                        Kembali ke riwayat Batal/Rusak
                    </Link>
                </Button>
            </main>
        </>
    );
}
