import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, History, Pencil, Users } from 'lucide-react';
import { LoketDeleteDialog } from '@/components/lokets/loket-delete-dialog';
import { formatDateTime } from '@/components/inventory/format';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit, index } from '@/routes/lokets';

type Props = {
    loket: {
        id: number;
        code: string;
        name: string;
        description: string | null;
        is_active: boolean;
        users_count: number;
        allocations_count: number;
        baps_count: number;
        created_at: string;
        can: { delete: boolean };
        users: {
            id: number;
            username: string;
            name: string;
            nip: string | null;
            role: string;
            is_active: boolean;
        }[];
        timeline: {
            id: number;
            event: string;
            actor: string;
            created_at: string;
        }[];
    };
};

export default function ShowLoket({ loket }: Props) {
    return (
        <>
            <Head title={loket.name} />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div className="space-y-2">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={index()}>
                                <ArrowLeft data-icon="inline-start" />
                                Kembali ke Master Loket
                            </Link>
                        </Button>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {loket.name}
                            </h1>
                            <Badge
                                variant={
                                    loket.is_active
                                        ? 'secondary'
                                        : 'destructive'
                                }
                            >
                                {loket.is_active ? 'Aktif' : 'Tidak aktif'}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground font-mono text-sm">
                            {loket.code}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(loket.id)}>
                                <Pencil data-icon="inline-start" />
                                Edit Loket
                            </Link>
                        </Button>
                        {loket.can.delete ? (
                            <LoketDeleteDialog loket={loket} />
                        ) : null}
                    </div>
                </div>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Identitas</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm">
                            <DetailRow label="Kode" value={loket.code} mono />
                            <DetailRow
                                label="Status"
                                value={
                                    loket.is_active ? 'Aktif' : 'Tidak aktif'
                                }
                            />
                            <DetailRow
                                label="Dibuat pada"
                                value={formatDateTime(loket.created_at)}
                            />
                        </CardContent>
                    </Card>
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Deskripsi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-muted-foreground text-sm leading-6">
                                {loket.description ?? 'Tidak ada deskripsi.'}
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <Card>
                    <CardHeader className="flex-row flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <Users className="text-muted-foreground size-4" />
                            <CardTitle>Pengguna yang ditugaskan</CardTitle>
                        </div>
                        <p className="text-muted-foreground text-sm tabular-nums">
                            {loket.users_count} pengguna ·{' '}
                            {loket.allocations_count} alokasi ·{' '}
                            {loket.baps_count} BAP
                        </p>
                    </CardHeader>
                    <CardContent>
                        {loket.users.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Belum ada pengguna yang ditugaskan pada Loket
                                ini.
                            </p>
                        ) : (
                            <>
                                <div className="hidden overflow-x-auto sm:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Nama</TableHead>
                                                <TableHead>Username</TableHead>
                                                <TableHead>NIP</TableHead>
                                                <TableHead>Role</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {loket.users.map((user) => (
                                                <TableRow key={user.id}>
                                                    <TableCell className="font-medium">
                                                        {user.name}
                                                    </TableCell>
                                                    <TableCell>
                                                        {user.username}
                                                    </TableCell>
                                                    <TableCell className="font-mono text-xs tabular-nums">
                                                        {user.nip ?? '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {user.role}
                                                    </TableCell>
                                                    <TableCell>
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
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                                <div className="grid gap-3 sm:hidden">
                                    {loket.users.map((user) => (
                                        <div
                                            key={user.id}
                                            className="rounded-lg border p-4"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">
                                                        {user.name}
                                                    </p>
                                                    <p className="text-muted-foreground text-sm">
                                                        {user.username}
                                                    </p>
                                                </div>
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
                                            </div>
                                            <p className="text-muted-foreground mt-3 font-mono text-xs tabular-nums">
                                                {user.nip ?? '—'} · {user.role}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex-row items-center gap-2">
                        <History className="text-muted-foreground size-4" />
                        <CardTitle>Riwayat singkat</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {loket.timeline.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Belum ada riwayat yang dapat ditampilkan.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {loket.timeline.map((entry, entryIndex) => (
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

function DetailRow({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span
                className={`text-right font-medium ${mono ? 'font-mono text-xs whitespace-nowrap' : ''}`}
            >
                {value}
            </span>
        </div>
    );
}

ShowLoket.layout = {
    breadcrumbs: [
        { title: 'Master Loket', href: index() },
        { title: 'Detail Loket', href: index() },
    ],
};
