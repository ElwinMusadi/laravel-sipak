import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Search, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { EmptyState } from '@/components/app/empty-state';
import { Pagination } from '@/components/inventory/pagination';
import type { PaginationLink } from '@/components/inventory/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, index, show } from '@/routes/lokets';

type Loket = {
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
};

type Props = {
    lokets: {
        data: Loket[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; status?: 'active' | 'inactive' };
};

export default function LoketIndex({ lokets, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            index.url(),
            { search: search || undefined, status: status || undefined },
            { preserveState: true, replace: true },
        );
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('');
        router.get(index.url(), {}, { preserveState: true, replace: true });
    };

    return (
        <>
            <Head title="Master Loket" />

            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div className="space-y-1.5">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Master Loket
                        </h1>
                        <p className="text-muted-foreground max-w-2xl text-sm">
                            Kelola kode, nama, status, dan metadata Loket tanpa
                            mengubah riwayat alokasi atau BAP.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus data-icon="inline-start" />
                            Tambah Loket
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent>
                        <form
                            onSubmit={applyFilters}
                            className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_11rem_auto]"
                        >
                            <div className="relative">
                                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    className="pl-9"
                                    placeholder="Cari kode atau nama Loket"
                                />
                            </div>
                            <Select
                                value={status || 'all'}
                                onValueChange={(value) =>
                                    setStatus(
                                        value === 'all'
                                            ? ''
                                            : (value as 'active' | 'inactive'),
                                    )
                                }
                            >
                                <SelectTrigger aria-label="Filter status Loket">
                                    <SelectValue placeholder="Semua status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">
                                            Semua status
                                        </SelectItem>
                                        <SelectItem value="active">
                                            Aktif
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Tidak aktif
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button type="submit">Terapkan</Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={resetFilters}
                                    aria-label="Reset filter"
                                >
                                    <X />
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {lokets.data.length === 0 ? (
                    <Card>
                        <CardContent>
                            <EmptyState
                                title="Belum ada Loket."
                                description="Tambahkan Loket untuk mulai menugaskan Petugas Loket dan menerima alokasi."
                            />
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card className="hidden sm:block">
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Kode</TableHead>
                                            <TableHead>Nama Loket</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Riwayat</TableHead>
                                            <TableHead className="text-right">
                                                Aksi
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {lokets.data.map((loket) => (
                                            <TableRow key={loket.id}>
                                                <TableCell className="font-mono text-xs font-medium">
                                                    {loket.code}
                                                </TableCell>
                                                <TableCell>
                                                    <p className="font-medium">
                                                        {loket.name}
                                                    </p>
                                                    {loket.description ? (
                                                        <p className="text-muted-foreground max-w-md truncate text-xs">
                                                            {loket.description}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge
                                                        isActive={
                                                            loket.is_active
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="text-muted-foreground text-sm tabular-nums">
                                                    {loket.users_count} pengguna
                                                    · {loket.allocations_count}{' '}
                                                    alokasi · {loket.baps_count}{' '}
                                                    BAP
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={show(
                                                                    loket.id,
                                                                )}
                                                                aria-label={`Detail ${loket.name}`}
                                                            >
                                                                <Eye />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={edit(
                                                                    loket.id,
                                                                )}
                                                                aria-label={`Edit ${loket.name}`}
                                                            >
                                                                <Pencil />
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <div className="grid gap-3 sm:hidden">
                            {lokets.data.map((loket) => (
                                <Card key={loket.id}>
                                    <CardContent className="grid gap-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-mono text-xs font-medium">
                                                    {loket.code}
                                                </p>
                                                <p className="mt-1 font-medium">
                                                    {loket.name}
                                                </p>
                                            </div>
                                            <StatusBadge
                                                isActive={loket.is_active}
                                            />
                                        </div>
                                        {loket.description ? (
                                            <p className="text-muted-foreground text-sm">
                                                {loket.description}
                                            </p>
                                        ) : null}
                                        <p className="text-muted-foreground text-sm tabular-nums">
                                            {loket.users_count} pengguna ·{' '}
                                            {loket.allocations_count} alokasi ·{' '}
                                            {loket.baps_count} BAP
                                        </p>
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={show(loket.id)}>
                                                    <Eye data-icon="inline-start" />
                                                    Detail
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit(loket.id)}>
                                                    <Pencil data-icon="inline-start" />
                                                    Edit
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </>
                )}

                <div className="flex flex-col justify-between gap-3 text-sm sm:flex-row sm:items-center">
                    <p className="text-muted-foreground">
                        Menampilkan {lokets.from ?? 0}–{lokets.to ?? 0} dari{' '}
                        {lokets.total} Loket
                    </p>
                    <Pagination links={lokets.links} />
                </div>
            </main>
        </>
    );
}

function StatusBadge({ isActive }: { isActive: boolean }) {
    return (
        <Badge variant={isActive ? 'secondary' : 'destructive'}>
            {isActive ? 'Aktif' : 'Tidak aktif'}
        </Badge>
    );
}

LoketIndex.layout = {
    breadcrumbs: [{ title: 'Master Loket', href: index() }],
};
