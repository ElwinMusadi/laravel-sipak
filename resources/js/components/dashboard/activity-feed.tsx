import { Activity } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { DashboardActivity } from './dashboard-data';

export function ActivityFeed({
    items,
}: {
    items: readonly DashboardActivity[];
}) {
    return (
        <Card className="rounded-xl">
            <CardHeader>
                <div className="flex items-center gap-3">
                    <span className="bg-muted text-muted-foreground flex size-8 items-center justify-center rounded-lg">
                        <Activity className="size-4" />
                    </span>
                    <div>
                        <CardTitle>Aktivitas</CardTitle>
                        <CardDescription>
                            Perubahan terakhir pada antrean kerja.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-5">
                {items.map((item) => (
                    <div key={item.id} className="flex gap-3">
                        <span className="bg-border mt-2 size-2 shrink-0 rounded-full" />
                        <div className="min-w-0">
                            <p className="text-sm">
                                <span className="font-medium">
                                    {item.actor}
                                </span>{' '}
                                {item.description}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">
                                {item.occurredAt}
                            </p>
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
