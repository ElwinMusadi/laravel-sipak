import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRightLeft, History } from 'lucide-react';
import { AllocationStatusBadge } from '@/components/allocation/allocation-status-badge';
import { BoxStatusBadge } from '@/components/inventory/box-status-badge';
import {
    formatDate,
    formatDateTime,
    formatNomeratur,
    formatQuantity,
    formatRange,
} from '@/components/inventory/format';
import type { SkpdBoxSummary } from '@/components/inventory/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    create as createAllocation,
    show as allocationShow,
} from '@/routes/skpd/allocations';
import { index } from '@/routes/skpd/boxes';

type Allocation = {
    id: number;
    numerator_start: number;
    numerator_end: number;
    quantity: number;
    used_quantity: number;
    remaining_quantity: number;
    status: 'pending' | 'accepted' | 'completed' | 'cancelled';
    loket: { id: number; name: string };
    created_by: string;
    created_at: string;
    accepted_by: string | null;
    accepted_at: string | null;
};

type Props = {
    box: SkpdBoxSummary & {
        creator: { id: number; name: string };
        allocations: Allocation[];
        timeline: {
            id: number;
            event: string;
            actor: string;
            created_at: string;
        }[];
    };
    can: { createAllocation: boolean };
};

export default function ShowBox({ box, can }: Props) {
    return (
        <>
            <Head title={`Box ${box.box_number}`} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div className="space-y-2">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={index()}>
                                <ArrowLeft />
                                Kembali ke Box
                            </Link>
                        </Button>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {box.box_number}
                            </h1>
                            <BoxStatusBadge status={box.status} />
                        </div>
                        <p className="text-muted-foreground font-mono text-sm">
                            {formatRange(
                                box.numerator_start,
                                box.numerator_end,
                            )}
                        </p>
                    </div>
                    {can.createAllocation && box.available_quantity > 0 ? (
                        <Button asChild>
                            <Link href={createAllocation()}>
                                <ArrowRightLeft />
                                Buat alokasi
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Identitas</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm">
                            <DetailRow
                                label="Tanggal diterima"
                                value={formatDate(box.received_at)}
                            />
                            <DetailRow
                                label="Didaftarkan oleh"
                                value={box.creator.name}
                            />
                            <DetailRow
                                label="Loket penerima"
                                value={
                                    box.loket?.name ?? 'Belum ada alokasi aktif'
                                }
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Range</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm">
                            <DetailRow
                                label="Nomeratur awal"
                                value={formatNomeratur(box.numerator_start)}
                            />
                            <DetailRow
                                label="Nomeratur akhir"
                                value={formatNomeratur(box.numerator_end)}
                            />
                            <DetailRow
                                label="Total"
                                value={`${formatQuantity(box.total_sets)} set`}
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Inventory</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm">
                            <DetailRow
                                label="Tersedia"
                                value={`${formatQuantity(box.available_quantity)} set`}
                            />
                            <DetailRow
                                label="Dialokasikan"
                                value={`${formatQuantity(box.allocated_quantity)} set`}
                            />
                            <DetailRow
                                label="Terpakai"
                                value={`${formatQuantity(box.used_quantity)} set`}
                            />
                        </CardContent>
                    </Card>
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat alokasi</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {box.allocations.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Belum ada distribusi SKPD dari Box ini.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[45rem] text-sm">
                                    <thead className="text-muted-foreground border-b text-left">
                                        <tr>
                                            <th className="px-2 py-3 font-medium">
                                                Loket
                                            </th>
                                            <th className="px-2 py-3 font-medium">
                                                Range
                                            </th>
                                            <th className="px-2 py-3 font-medium">
                                                Quantity
                                            </th>
                                            <th className="px-2 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-2 py-3 font-medium">
                                                Diterima
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {box.allocations.map((allocation) => (
                                            <tr key={allocation.id}>
                                                <td className="px-2 py-3">
                                                    <Link
                                                        href={allocationShow(
                                                            allocation.id,
                                                        )}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {allocation.loket.name}
                                                    </Link>
                                                    <p className="text-muted-foreground text-xs">
                                                        Dibuat oleh{' '}
                                                        {allocation.created_by}
                                                    </p>
                                                </td>
                                                <td className="px-2 py-3 font-mono text-xs whitespace-nowrap">
                                                    {formatRange(
                                                        allocation.numerator_start,
                                                        allocation.numerator_end,
                                                    )}
                                                </td>
                                                <td className="px-2 py-3">
                                                    {formatQuantity(
                                                        allocation.quantity,
                                                    )}{' '}
                                                    set
                                                    <p className="text-muted-foreground text-xs">
                                                        Sisa{' '}
                                                        {formatQuantity(
                                                            allocation.remaining_quantity,
                                                        )}
                                                    </p>
                                                </td>
                                                <td className="px-2 py-3">
                                                    <AllocationStatusBadge
                                                        status={
                                                            allocation.status
                                                        }
                                                    />
                                                </td>
                                                <td className="text-muted-foreground px-2 py-3">
                                                    {allocation.accepted_at
                                                        ? `${allocation.accepted_by ?? '—'} · ${formatDateTime(allocation.accepted_at)}`
                                                        : 'Belum diterima'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex-row items-center gap-2">
                        <History className="text-muted-foreground size-4" />
                        <CardTitle>Riwayat singkat</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {box.timeline.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Belum ada riwayat yang dapat ditampilkan.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {box.timeline.map((entry, entryIndex) => (
                                    <div key={entry.id}>
                                        {entryIndex > 0 ? (
                                            <Separator className="mb-4" />
                                        ) : null}
                                        <p className="font-medium">
                                            {entry.event}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {entry.actor} ·{' '}
                                            {formatDateTime(entry.created_at)}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </main>
        </>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

ShowBox.layout = {
    breadcrumbs: [
        { title: 'Box SKPD', href: index() },
        { title: 'Detail Box', href: index() },
    ],
};
