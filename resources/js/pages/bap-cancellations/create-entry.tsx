import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileWarning } from 'lucide-react';
import { EmptyState } from '@/components/app/empty-state';
import { formatDate, formatRange } from '@/components/inventory/format';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create as createCancellation } from '@/routes/baps/cancellations';
import { create, index } from '@/routes/bap-cancellations';

type Props = {
    baps: {
        id: number;
        service_date: string;
        loket: string;
        numerator_start: number;
        numerator_end: number;
        created_by: string;
    }[];
};

export default function CreateBapCancellationEntry({ baps }: Props) {
    return (
        <>
            <Head title="Catat BAP Batal/Rusak" />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="space-y-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={index()}>
                            <ArrowLeft data-icon="inline-start" />
                            Kembali ke BAP Batal/Rusak
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Pilih Draft BAP
                    </h1>
                    <p className="text-muted-foreground max-w-2xl text-sm">
                        Pilih BAP draft yang akan dicatatkan nomeratur batal
                        atau rusaknya. Catatan yang dibuat bersifat historis dan
                        tidak mengubah ledger persediaan.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Draft BAP yang dapat dicatat</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {baps.length === 0 ? (
                            <EmptyState
                                title="Tidak ada draft BAP yang tersedia."
                                description="BAP batal/rusak hanya dapat dicatat pada draft BAP yang berada dalam kewenangan Anda."
                            />
                        ) : (
                            <>
                                <div className="hidden overflow-x-auto sm:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>BAP</TableHead>
                                                <TableHead>Tanggal</TableHead>
                                                <TableHead>Loket</TableHead>
                                                <TableHead>Nomeratur</TableHead>
                                                <TableHead>
                                                    Dibuat oleh
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Aksi
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {baps.map((bap) => (
                                                <TableRow key={bap.id}>
                                                    <TableCell className="font-medium tabular-nums">
                                                        #{bap.id}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatDate(
                                                            bap.service_date,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {bap.loket}
                                                    </TableCell>
                                                    <TableCell className="font-mono text-xs whitespace-nowrap">
                                                        {formatRange(
                                                            bap.numerator_start,
                                                            bap.numerator_end,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {bap.created_by}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Button
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={createCancellation(
                                                                    bap.id,
                                                                )}
                                                            >
                                                                <FileWarning data-icon="inline-start" />
                                                                Pilih
                                                            </Link>
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                                <div className="grid gap-3 sm:hidden">
                                    {baps.map((bap) => (
                                        <div
                                            key={bap.id}
                                            className="grid gap-3 rounded-lg border p-4"
                                        >
                                            <div className="flex justify-between gap-3">
                                                <p className="font-medium">
                                                    BAP #{bap.id}
                                                </p>
                                                <p className="text-muted-foreground text-sm">
                                                    {formatDate(
                                                        bap.service_date,
                                                    )}
                                                </p>
                                            </div>
                                            <p className="text-sm">
                                                {bap.loket}
                                            </p>
                                            <p className="font-mono text-xs">
                                                {formatRange(
                                                    bap.numerator_start,
                                                    bap.numerator_end,
                                                )}
                                            </p>
                                            <Button size="sm" asChild>
                                                <Link
                                                    href={createCancellation(
                                                        bap.id,
                                                    )}
                                                >
                                                    <FileWarning data-icon="inline-start" />
                                                    Catat batal/rusak
                                                </Link>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </main>
        </>
    );
}

CreateBapCancellationEntry.layout = {
    breadcrumbs: [
        { title: 'BAP Batal/Rusak', href: index() },
        { title: 'Pilih Draft BAP', href: create() },
    ],
};
