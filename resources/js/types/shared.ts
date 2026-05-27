export interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface SharedProps {
    flash: FlashMessages;
    auth: {
        user: AuthUser | null;
    };
    app: {
        name: string;
        url: string;
    };
    sidebarOpen: boolean;
}

export interface PageProps<T = Record<string, unknown>> extends SharedProps {
    [key: string]: unknown;
}
