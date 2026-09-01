import { Form, Head, Link } from '@inertiajs/react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { edit, index } from '@/routes/users';
import type { ManagedUser } from '@/components/users/user-form-fields';

type Props = {
    user: ManagedUser & {
        role_label: string;
        last_login_at: string | null;
    };
};

export default function ShowUser({ user }: Props) {
    return (
        <>
            <Head title={user.name} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <Heading
                        title={user.name}
                        description={`Username: ${user.username}`}
                    />
                    <Button asChild variant="outline">
                        <Link href={edit(user.id)}>Edit pengguna</Link>
                    </Button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,0.8fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi akun</CardTitle>
                            <CardDescription>
                                Password tidak pernah ditampilkan atau dikirim
                                ke halaman ini.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Role
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {user.role_label}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        NIP
                                    </dt>
                                    <dd className="mt-1 font-mono text-sm font-medium tabular-nums">
                                        {user.nip}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Loket
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {user.loket?.name ?? 'Tidak ditetapkan'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Status
                                    </dt>
                                    <dd className="mt-1">
                                        <Badge
                                            variant={
                                                user.is_active
                                                    ? 'secondary'
                                                    : 'destructive'
                                            }
                                        >
                                            {user.is_active
                                                ? 'Aktif'
                                                : 'Tidak aktif'}
                                        </Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Login terakhir
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {user.last_login_at
                                            ? new Intl.DateTimeFormat('id-ID', {
                                                  dateStyle: 'medium',
                                                  timeStyle: 'short',
                                              }).format(
                                                  new Date(user.last_login_at),
                                              )
                                            : 'Belum pernah'}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Reset password</CardTitle>
                            <CardDescription>
                                Tetapkan password sementara baru tanpa melihat
                                password lama.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...UserManagementController.resetPassword.form(
                                    user.id,
                                )}
                                resetOnError={[
                                    'password',
                                    'password_confirmation',
                                ]}
                                resetOnSuccess
                                className="space-y-5"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="password">
                                                Password sementara
                                            </Label>
                                            <PasswordInput
                                                id="password"
                                                name="password"
                                                required
                                                autoComplete="new-password"
                                                placeholder="Masukkan password baru"
                                            />
                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="password_confirmation">
                                                Konfirmasi password
                                            </Label>
                                            <PasswordInput
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                required
                                                autoComplete="new-password"
                                                placeholder="Ulangi password baru"
                                            />
                                            <InputError
                                                message={
                                                    errors.password_confirmation
                                                }
                                            />
                                        </div>
                                        <Button disabled={processing}>
                                            Reset password
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

ShowUser.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index() },
        { title: 'Detail pengguna', href: index() },
    ],
};
