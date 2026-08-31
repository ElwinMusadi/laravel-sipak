import { Head } from '@inertiajs/react';
import { BapForm } from '@/components/bap/bap-form';
import Heading from '@/components/heading';
import { create, index } from '@/routes/baps';

type Props = {
    loket: { id: number; name: string };
    default_service_date: string;
    expected_numerator_start: number | null;
    allocations: {
        id: number;
        numerator_start: number;
        numerator_end: number;
        remaining_quantity: number;
    }[];
};

export default function CreateBap({
    loket,
    default_service_date,
    expected_numerator_start,
    allocations,
}: Props) {
    return (
        <>
            <Head title="Buat BAP SKPD" />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Buat BAP SKPD"
                    description="Catat pemakaian aktual setelah pelayanan Loket selesai. Total selalu dihitung dari range nomeratur."
                />

                <BapForm
                    mode="create"
                    loket={loket}
                    defaultServiceDate={default_service_date}
                    expectedNumeratorStart={expected_numerator_start}
                    allocations={allocations}
                />
            </main>
        </>
    );
}

CreateBap.layout = {
    breadcrumbs: [
        { title: 'BAP SKPD', href: index() },
        { title: 'Buat BAP', href: create() },
    ],
};
