import { Form, Head, Link } from '@inertiajs/react';
import LoketController from '@/actions/App/Http/Controllers/LoketController';
import Heading from '@/components/heading';
import { LoketFormFields } from '@/components/lokets/loket-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, index } from '@/routes/lokets';

export default function CreateLoket() {
    return (
        <>
            <Head title="Tambah Loket" />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Tambah Loket"
                    description="Loket baru langsung aktif dan dapat dipakai untuk penugasan, alokasi, serta BAP baru."
                />

                <Card className="max-w-3xl">
                    <CardContent className="pt-6">
                        <Form
                            {...LoketController.store.form()}
                            className="space-y-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <LoketFormFields errors={errors} />
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={index()}>Batal</Link>
                                        </Button>
                                        <Button disabled={processing}>
                                            Simpan Loket
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

CreateLoket.layout = {
    breadcrumbs: [
        { title: 'Master Loket', href: index() },
        { title: 'Tambah Loket', href: create() },
    ],
};
