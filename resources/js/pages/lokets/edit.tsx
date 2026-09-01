import { Form, Head, Link } from '@inertiajs/react';
import LoketController from '@/actions/App/Http/Controllers/LoketController';
import Heading from '@/components/heading';
import {
    LoketFormFields,
    type LoketFormData,
} from '@/components/lokets/loket-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index, show } from '@/routes/lokets';

type Props = { loket: LoketFormData };

export default function EditLoket({ loket }: Props) {
    return (
        <>
            <Head title={`Edit ${loket.name}`} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Edit Loket"
                    description="Penonaktifan hanya tersedia jika tidak ada pengguna yang masih ditugaskan pada Loket ini."
                />

                <Card className="max-w-3xl">
                    <CardContent className="pt-6">
                        <Form
                            {...LoketController.update.form(loket.id)}
                            className="space-y-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <LoketFormFields
                                        loket={loket}
                                        errors={errors}
                                        includeStatus
                                    />
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={show(loket.id)}>
                                                Batal
                                            </Link>
                                        </Button>
                                        <Button disabled={processing}>
                                            Simpan perubahan
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

EditLoket.layout = {
    breadcrumbs: [
        { title: 'Master Loket', href: index() },
        { title: 'Edit Loket', href: index() },
    ],
};
