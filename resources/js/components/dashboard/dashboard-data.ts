export type DashboardStatus =
    | 'draft'
    | 'waiting'
    | 'clarification'
    | 'revision'
    | 'completed';

export type DashboardMetric = {
    id: 'today' | 'waiting' | 'clarification' | 'completed';
    label: string;
    value: number;
    description: string;
};

export type DashboardWorkItem = {
    id: 'waiting' | 'clarification' | 'revision';
    title: string;
    count: number;
    status: DashboardStatus;
    description: string;
};

export type RecentBap = {
    number: string;
    loket: string;
    submittedAt: string;
    status: DashboardStatus;
};

export type DashboardActivity = {
    id: string;
    actor: string;
    description: string;
    occurredAt: string;
};

export type DashboardPresentationData = {
    metrics: readonly DashboardMetric[];
    workItems: readonly DashboardWorkItem[];
    recentBaps: readonly RecentBap[];
    activities: readonly DashboardActivity[];
};

export const dashboardPresentationData: DashboardPresentationData = {
    metrics: [
        {
            id: 'today',
            label: 'BAP Hari Ini',
            value: 12,
            description: 'Dicatat sepanjang hari ini',
        },
        {
            id: 'waiting',
            label: 'Menunggu Verifikasi',
            value: 5,
            description: 'Perlu ditindaklanjuti',
        },
        {
            id: 'clarification',
            label: 'Perlu Klarifikasi',
            value: 2,
            description: 'Menunggu respons loket',
        },
        {
            id: 'completed',
            label: 'Selesai Hari Ini',
            value: 8,
            description: 'Telah diselesaikan',
        },
    ],
    workItems: [
        {
            id: 'waiting',
            title: 'BAP menunggu verifikasi',
            count: 5,
            status: 'waiting',
            description: 'Prioritas utama untuk antrean saat ini.',
        },
        {
            id: 'clarification',
            title: 'BAP membutuhkan klarifikasi',
            count: 2,
            status: 'clarification',
            description: 'Perlu ditinjau sebelum proses dapat dilanjutkan.',
        },
        {
            id: 'revision',
            title: 'BAP membutuhkan revisi',
            count: 3,
            status: 'revision',
            description: 'Menunggu pembaruan dari petugas terkait.',
        },
    ],
    recentBaps: [
        {
            number: 'BAP-2026-08-0012',
            loket: 'SAMSAT Induk',
            submittedAt: '30 Agu 2026, 10.42',
            status: 'waiting',
        },
        {
            number: 'BAP-2026-08-0011',
            loket: 'SAMSAT Corner Lippo',
            submittedAt: '30 Agu 2026, 09.58',
            status: 'clarification',
        },
        {
            number: 'BAP-2026-08-0010',
            loket: 'SAMSAT Keliling',
            submittedAt: '30 Agu 2026, 09.14',
            status: 'revision',
        },
        {
            number: 'BAP-2026-08-0009',
            loket: 'MPP Kota Kupang',
            submittedAt: '30 Agu 2026, 08.35',
            status: 'completed',
        },
    ],
    activities: [
        {
            id: 'activity-1',
            actor: 'Petugas Loket',
            description: 'mengirim BAP-2026-08-0012 untuk verifikasi.',
            occurredAt: '10 menit lalu',
        },
        {
            id: 'activity-2',
            actor: 'Petugas Verifikasi',
            description: 'mengajukan klarifikasi pada BAP-2026-08-0011.',
            occurredAt: '32 menit lalu',
        },
        {
            id: 'activity-3',
            actor: 'Kasie Verifikasi',
            description: 'menyelesaikan persetujuan BAP-2026-08-0009.',
            occurredAt: '1 jam lalu',
        },
    ],
};
