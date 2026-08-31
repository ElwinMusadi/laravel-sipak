export type User = {
    id: number;
    username: string;
    name: string;
    email: string | null;
    avatar?: string;
    role: string;
    loket_id: number | null;
    is_active: boolean;
    last_login_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    permissions: {
        manageUsers: boolean;
        viewSkpdInventory: boolean;
        viewCentralSkpdInventory: boolean;
        manageSkpdInventory: boolean;
        viewBaps: boolean;
        createBap: boolean;
        viewBapCancellations: boolean;
        viewBapVerificationsPhase1: boolean;
    };
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
