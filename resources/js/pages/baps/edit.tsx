import { Head } from '@inertiajs/react';
import { BapForm } from '@/components/bap/bap-form';
import Heading from '@/components/heading';
import { index } from '@/routes/baps';

type Props = {
    bap: {
        id: number;
        service_date: string;
        numerator_start: number;
        numerator_end: number;
        online_usage_count: number;
        loket: { id: number; name: string };
    };
};

export default function EditBap({ bap }: Props) {
    return (
        <>
            <Head title={`Ubah draft BAP #${bap.id}`} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title={`Ubah draft BAP #${bap.id}`}
                    description="Periksa kembali range, total otomatis, dan pemakaian online sebelum BAP diajukan."
                />

                <BapForm mode="edit" bap={bap} />
            </main>
        </>
    );
}

EditBap.layout = {
    breadcrumbs: [
        { title: 'BAP SKPD', href: index() },
        { title: 'Detail BAP', href: index() },
        { title: 'Ubah draft', href: index() },
    ],
};
