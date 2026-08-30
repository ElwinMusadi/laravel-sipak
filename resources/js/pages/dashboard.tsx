import { Head, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { ActivityFeed } from '@/components/dashboard/activity-feed';
import {
    dashboardPresentationData,
    type DashboardPresentationData,
} from '@/components/dashboard/dashboard-data';
import { DashboardMetrics } from '@/components/dashboard/dashboard-metrics';
import { InventorySummary } from '@/components/dashboard/inventory-summary';
import { MyWork } from '@/components/dashboard/my-work';
import { RecentBaps } from '@/components/dashboard/recent-baps';
import { dashboard } from '@/routes';

type Props = {
    dashboard?: DashboardPresentationData;
};

export default function Dashboard({ dashboard: dashboardData }: Props) {
    const { auth } = usePage().props;
    const presentation = dashboardData ?? dashboardPresentationData;

    return (
        <>
            <Head title="Dashboard" />
            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <section className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div className="space-y-1.5">
                        <p className="text-muted-foreground text-sm">
                            SAMSAT Kota Kupang
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Selamat datang, {auth.user?.name ?? 'Pengguna'}
                        </h1>
                        <p className="text-muted-foreground max-w-2xl text-sm text-pretty">
                            Prioritaskan pekerjaan yang membutuhkan tindak
                            lanjut hari ini.
                        </p>
                    </div>
                    <Badge variant="outline" className="w-fit">
                        Data presentasi
                    </Badge>
                </section>

                <DashboardMetrics metrics={presentation.metrics} />

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(20rem,1fr)]">
                    <MyWork items={presentation.workItems} />
                    <ActivityFeed items={presentation.activities} />
                </section>

                <section className="grid gap-6 2xl:grid-cols-[minmax(0,1.5fr)_minmax(22rem,1fr)]">
                    <RecentBaps items={presentation.recentBaps} />
                    <InventorySummary />
                </section>
            </main>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
