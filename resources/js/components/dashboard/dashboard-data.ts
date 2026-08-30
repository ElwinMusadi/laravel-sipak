export type DashboardStatus = 'draft' | 'waiting';

export type DashboardMetric = {
    id: 'today' | 'waiting';
    label: string;
    value: number;
    description: string;
};

export type DashboardWorkItem = {
    id: 'waiting';
    title: string;
    count: number;
    status: DashboardStatus;
    description: string;
};

export type RecentBap = {
    id: number;
    loket: string;
    serviceDate: string;
    submittedAt: string | null;
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
};
