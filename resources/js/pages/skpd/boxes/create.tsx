import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import SkpdBoxController from '@/actions/App/Http/Controllers/SkpdBoxController';
import InputError from '@/components/input-error';
import { RangeInputFields } from '@/components/inventory/range-input-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Heading from '@/components/heading';
import { create, index } from '@/routes/skpd/boxes';

export default function CreateBox() {
    const [numeratorStart, setNumeratorStart] = useState('');
    const [numeratorEnd, setNumeratorEnd] = useState('');

    return (
        <>
            <Head title="Tambah Box SKPD" />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Tambah Box SKPD"
                    description="Daftarkan range nomeratur penerimaan pusat. Quantity akan dihitung dari rentang yang valid."
                />

                <Card className="max-w-3xl">
                    <CardContent>
                        <Form
                            {...SkpdBoxController.store.form()}
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
                                            placeholder="BOX-SKPD-001"
                                            aria-invalid={Boolean(
                                                errors.box_number,
                                            )}
                                        />
                                        <InputError
                                            message={errors.box_number}
                                        />
                                    </div>

                                    <RangeInputFields
                                        numeratorStart={numeratorStart}
                                        numeratorEnd={numeratorEnd}
                                        onNumeratorStartChange={
                                            setNumeratorStart
                                        }
                                        onNumeratorEndChange={setNumeratorEnd}
                                        errors={errors}
                                    />

                                    <div className="grid gap-2">
                                        <Label htmlFor="received_at">
                                            Tanggal diterima
                                        </Label>
                                        <Input
                                            id="received_at"
                                            name="received_at"
                                            type="date"
                                            defaultValue={new Date()
                                                .toISOString()
                                                .slice(0, 10)}
                                            aria-invalid={Boolean(
                                                errors.received_at,
                                            )}
                                        />
                                        <InputError
                                            message={errors.received_at}
                                        />
                                    </div>

                                    <div className="flex flex-wrap justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={index()}>Batal</Link>
                                        </Button>
                                        <Button disabled={processing}>
                                            Daftarkan Box
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

CreateBox.layout = {
    breadcrumbs: [
        { title: 'Box SKPD', href: index() },
        { title: 'Tambah Box', href: create() },
    ],
};
