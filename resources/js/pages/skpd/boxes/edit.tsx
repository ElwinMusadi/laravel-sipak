import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-react';
import SkpdBoxController from '@/actions/App/Http/Controllers/SkpdBoxController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { formatRange } from '@/components/inventory/format';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/skpd/boxes';

type Props = {
    box: {
        id: number;
        box_number: string;
        numerator_start: number;
        numerator_end: number;
        total_sets: number;
        central_storage_location: string;
        received_at: string;
    };
};

export default function EditBox({ box }: Props) {
    const [receivedAt, setReceivedAt] = useState<Date | undefined>(
        new Date(`${box.received_at}T00:00:00`),
    );

    return (
        <>
            <Head title={`Edit ${box.box_number}`} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Edit Metadata Box"
                    description={`Range ${formatRange(box.numerator_start, box.numerator_end)} dan total ${box.total_sets} set bersifat immutable untuk menjaga ledger persediaan.`}
                />

                <Card className="max-w-3xl">
                    <CardContent className="pt-6">
                        <Form
                            {...SkpdBoxController.update.form(box.id)}
                            className="space-y-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="box_number">
                                            Nomor / referensi Box
                                        </Label>
                                        <Input
                                            id="box_number"
                                            name="box_number"
                                            defaultValue={box.box_number}
                                            required
                                            aria-invalid={Boolean(
                                                errors.box_number,
                                            )}
                                        />
                                        <InputError
                                            message={errors.box_number}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="central_storage_location">
                                            Lokasi penyimpanan pusat
                                        </Label>
                                        <Input
                                            id="central_storage_location"
                                            name="central_storage_location"
                                            defaultValue={
                                                box.central_storage_location
                                            }
                                            required
                                            aria-invalid={Boolean(
                                                errors.central_storage_location,
                                            )}
                                        />
                                        <InputError
                                            message={
                                                errors.central_storage_location
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="received_at">
                                            Tanggal diterima
                                        </Label>
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        'w-full justify-start text-left font-normal',
                                                        !receivedAt &&
                                                            'text-muted-foreground',
                                                        errors.received_at &&
                                                            'border-destructive text-destructive',
                                                    )}
                                                >
                                                    <CalendarIcon data-icon="inline-start" />
                                                    {receivedAt ? (
                                                        format(
                                                            receivedAt,
                                                            'PPP',
                                                            {
                                                                locale: id,
                                                            },
                                                        )
                                                    ) : (
                                                        <span>
                                                            Pilih tanggal
                                                        </span>
                                                    )}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                className="w-auto p-0"
                                                align="start"
                                            >
                                                <Calendar
                                                    mode="single"
                                                    selected={receivedAt}
                                                    onSelect={setReceivedAt}
                                                    locale={id}
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <input
                                            type="hidden"
                                            name="received_at"
                                            value={
                                                receivedAt
                                                    ? format(
                                                          receivedAt,
                                                          'yyyy-MM-dd',
                                                      )
                                                    : ''
                                            }
                                        />
                                        <InputError
                                            message={errors.received_at}
                                        />
                                    </div>

                                    <div className="flex flex-wrap justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={show(box.id)}>
                                                Batal
                                            </Link>
                                        </Button>
                                        <Button disabled={processing}>
                                            Simpan metadata
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}

EditBox.layout = {
    breadcrumbs: [
        { title: 'Box SKPD', href: index() },
        { title: 'Edit Box', href: index() },
    ],
};
