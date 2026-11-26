export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type Flash = {
    success?: string | null;
    error?: string | null;
    drawn_winner?: {
        full_name: string;
        employee_number: string;
        prize_label: string | null;
        prize_type?: 'major' | 'minor' | null;
    } | null;
};
