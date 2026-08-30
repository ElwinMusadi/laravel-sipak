import { Form, Head, Link } from '@inertiajs/react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import Heading from '@/components/heading';
import {
    UserFormFields,
    type LoketOption,
    type ManagedUser,
    type RoleOption,
} from '@/components/users/user-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index, show } from '@/routes/users';

type Props = {
    user: ManagedUser;
    roles: RoleOption[];
    lokets: LoketOption[];
};

export default function EditUser({ user, roles, lokets }: Props) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <Heading
                    title="Edit Pengguna"
                    description={`Perbarui data administratif untuk ${user.name}.`}
                />

                <Card className="max-w-3xl">
                    <CardContent className="pt-6">
                        <Form
                            {...UserManagementController.update.form(user.id)}
                            className="space-y-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <UserFormFields
                                        user={user}
                                        errors={errors}
                                        roles={roles}
                                        lokets={lokets}
                                    />
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <Button variant="outline" asChild>
                                            <Link href={show(user.id)}>
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

EditUser.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index() },
        { title: 'Edit pengguna', href: index() },
    ],
};
