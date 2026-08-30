import { Head, usePage } from '@inertiajs/react';
import type { DashboardPresentationData } from '@/components/dashboard/dashboard-data';
import { DashboardMetrics } from '@/components/dashboard/dashboard-metrics';
import { MyWork } from '@/components/dashboard/my-work';
import { RecentBaps } from '@/components/dashboard/recent-baps';
import { dashboard } from '@/routes';

type Props = { dashboard: DashboardPresentationData };

export default function Dashboard({ dashboard: dashboardData }: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
                <section className="space-y-1.5">
                    <p className="text-muted-foreground text-sm">
                        SAMSAT Kota Kupang
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Selamat datang, {auth.user?.name ?? 'Pengguna'}
                    </h1>
                    <p className="text-muted-foreground max-w-2xl text-sm text-pretty">
                        Prioritaskan BAP Pemakaian yang membutuhkan tindak
                        lanjut.
                    </p>
                </section>

                <DashboardMetrics metrics={dashboardData.metrics} />

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
                    <MyWork items={dashboardData.workItems} />
                    <RecentBaps items={dashboardData.recentBaps} />
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
