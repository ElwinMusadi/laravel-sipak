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
                    Catatan presentasi untuk daftar BAP terakhir.
                </CardDescription>
            </CardHeader>
            <CardContent className="overflow-x-auto px-0">
                <Table className="min-w-155">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="pl-6">Nomor BAP</TableHead>
                            <TableHead>Loket</TableHead>
                            <TableHead>Waktu</TableHead>
                            <TableHead className="pr-6 text-right">
                                Status
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.map((item) => (
                            <TableRow key={item.number}>
                                <TableCell className="pl-6 font-medium">
                                    {item.number}
                                </TableCell>
                                <TableCell>{item.loket}</TableCell>
                                <TableCell className="text-muted-foreground">
                                    {item.submittedAt}
                                </TableCell>
                                <TableCell className="pr-6 text-right">
                                    <StatusBadge status={item.status} />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
