import { Form, Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import SkpdAllocationController from '@/actions/App/Http/Controllers/SkpdAllocationController';
import { EmptyState } from '@/components/app/empty-state';
import InputError from '@/components/input-error';
import { formatQuantity, formatRange } from '@/components/inventory/format';
import { RangeInputFields } from '@/components/inventory/range-input-fields';
import type { LoketOption } from '@/components/inventory/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import Heading from '@/components/heading';
import { create, index } from '@/routes/skpd/allocations';

type BoxOption = {
    id: number;
    box_number: string;
    numerator_start: number;
    numerator_end: number;
    total_sets: number;
    available_quantity: number;
};

type Props = {
    boxes: BoxOption[];
    lokets: LoketOption[];
};

export default function CreateAllocation({ boxes, lokets }: Props) {
    const [boxId, setBoxId] = useState('');
    const [loketId, setLoketId] = useState('');
    const [numeratorStart, setNumeratorStart] = useState('');
    const [numeratorEnd, setNumeratorEnd] = useState('');
    const selectedBox = useMemo(
        () => boxes.find((box) => box.id === Number(boxId)) ?? null,
        [boxId, boxes],
    );

    return (
        <>
            <Head title="Buat Alokasi SKPD" />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Buat Alokasi SKPD"
                    description="Pilih Box, tentukan Loket penerima, lalu masukkan rentang. Quantity dihitung otomatis dan validasi akhir dilakukan oleh ledger domain."
                />

                {boxes.length === 0 ? (
                    <Card className="max-w-3xl">
                        <EmptyState
                            title="Tidak ada Box yang tersedia."
                            description="Daftarkan Box baru atau selesaikan allocation pending sebelum membuat alokasi berikutnya."
                            action={
                                <Button variant="outline" asChild>
                                    <Link href={index()}>Kembali</Link>
                                </Button>
                            }
                        />
                    </Card>
                ) : (
                    <Card className="max-w-3xl">
                        <CardContent>
                            <Form
                                {...SkpdAllocationController.store.form()}
                                className="space-y-6"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="skpd_box_id">
                                                Box SKPD
                                            </Label>
                                            <select
                                                id="skpd_box_id"
                                                name="skpd_box_id"
                                                value={boxId}
                                                onChange={(event) =>
                                                    setBoxId(event.target.value)
                                                }
                                                className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                                aria-invalid={Boolean(
                                                    errors.skpd_box_id,
                                                )}
                                            >
                                                <option value="">
                                                    Pilih Box SKPD
                                                </option>
                                                {boxes.map((box) => (
                                                    <option
                                                        key={box.id}
                                                        value={box.id}
                                                    >
                                                        {box.box_number} ·{' '}
                                                        {formatRange(
                                                            box.numerator_start,
                                                            box.numerator_end,
                                                        )}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.skpd_box_id}
                                            />
                                        </div>

                                        {selectedBox ? (
                                            <Card
                                                size="sm"
                                                className="bg-muted/50 shadow-none"
                                            >
                                                <CardHeader>
                                                    <CardTitle>
                                                        Range Box terpilih
                                                    </CardTitle>
                                                </CardHeader>
                                                <CardContent className="grid gap-2 text-sm sm:grid-cols-2">
                                                    <p className="font-mono">
                                                        {formatRange(
                                                            selectedBox.numerator_start,
                                                            selectedBox.numerator_end,
                                                        )}
                                                    </p>
                                                    <p className="text-muted-foreground sm:text-right">
                                                        {formatQuantity(
                                                            selectedBox.available_quantity,
                                                        )}{' '}
                                                        set tersedia
                                                    </p>
                                                </CardContent>
                                            </Card>
                                        ) : null}

                                        <div className="grid gap-2">
                                            <Label htmlFor="loket_id">
                                                Loket penerima
                                            </Label>
                                            <select
                                                id="loket_id"
                                                name="loket_id"
                                                value={loketId}
                                                onChange={(event) =>
                                                    setLoketId(
                                                        event.target.value,
                                                    )
                                                }
                                                className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                                aria-invalid={Boolean(
                                                    errors.loket_id,
                                                )}
                                            >
                                                <option value="">
                                                    Pilih Loket
                                                </option>
                                                {lokets.map((loket) => (
                                                    <option
                                                        key={loket.id}
                                                        value={loket.id}
                                                    >
                                                        {loket.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.loket_id}
                                            />
                                        </div>

                                        <RangeInputFields
                                            numeratorStart={numeratorStart}
                                            numeratorEnd={numeratorEnd}
                                            onNumeratorStartChange={
                                                setNumeratorStart
                                            }
                                            onNumeratorEndChange={
                                                setNumeratorEnd
                                            }
                                            errors={errors}
                                        />

                                        <p className="text-muted-foreground rounded-xl border p-4 text-sm">
                                            Setelah dibuat, alokasi berstatus{' '}
                                            <strong>pending</strong>. Persediaan
                                            administratif Loket baru aktif saat
                                            Petugas Loket penerima melakukan
                                            handover digital.
                                        </p>

                                        <div className="flex flex-wrap justify-end gap-3">
                                            <Button variant="outline" asChild>
                                                <Link href={index()}>
                                                    Batal
                                                </Link>
                                            </Button>
                                            <Button disabled={processing}>
                                                Buat alokasi pending
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}
            </main>
        </>
    );
}

CreateAllocation.layout = {
    breadcrumbs: [
        { title: 'Distribusi / Alokasi', href: index() },
        { title: 'Buat alokasi', href: create() },
    ],
};
