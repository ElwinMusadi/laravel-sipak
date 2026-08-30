export type BoxStatus =
    | 'available'
    | 'partially_allocated'
    | 'fully_allocated'
    | 'depleted';

export type LoketOption = {
    id: number;
    name: string;
};

export type SkpdBoxSummary = {
    id: number;
    box_number: string;
    numerator_start: number;
    numerator_end: number;
    total_sets: number;
    pending_quantity: number;
    allocated_quantity: number;
    available_quantity: number;
    used_quantity: number;
    status: BoxStatus;
    loket: LoketOption | null;
    received_at: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
