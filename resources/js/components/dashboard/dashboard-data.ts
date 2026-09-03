export type DashboardStatus =
    | 'draft'
    | 'waiting'
    | 'in_progress'
    | 'discrepancy'
    | 'completed';

export type DashboardMetric = {
    id: 'today' | 'waiting' | 'in_progress' | 'discrepancy';
    label: string;
    value: number;
    description: string;
};

export type DashboardWorkItem = {
    id: string;
    title: string;
    count: number;
    status: DashboardStatus;
    description: string;
    href?: string;
};

export type RecentBap = {
    id: number;
    documentNumber: string;
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
