import { EmptyState } from '@/components/app/empty-state';
import { formatDate, formatDateTime } from '@/components/inventory/format';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { RecentBap } from './dashboard-data';
import { StatusBadge } from './status-badge';

export function RecentBaps({ items }: { items: readonly RecentBap[] }) {
    return (
        <Card className="rounded-xl">
            <CardHeader>
                <CardTitle>BAP Terbaru</CardTitle>
                <CardDescription>
                    BAP aktual pada scope akses Anda.
                </CardDescription>
            </CardHeader>
            <CardContent className="overflow-x-auto px-0">
                {items.length === 0 ? (
                    <EmptyState
                        title="Belum ada BAP SKPD."
                        description="BAP yang dibuat pada scope Anda akan muncul di sini."
                    />
                ) : (
                    <Table className="min-w-155">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6">Nomor dokumen</TableHead>
                                <TableHead>Loket</TableHead>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Waktu submit</TableHead>
                                <TableHead className="pr-6 text-right">
                                    Status
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="pl-6 font-medium whitespace-nowrap">
                                        {item.documentNumber}
                                    </TableCell>
                                    <TableCell>{item.loket}</TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(item.serviceDate)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground whitespace-nowrap">
                                        {item.submittedAt
                                            ? formatDateTime(item.submittedAt)
                                            : 'Belum diajukan'}
                                    </TableCell>
                                    <TableCell className="pr-6 text-right">
                                        <StatusBadge status={item.status} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}
