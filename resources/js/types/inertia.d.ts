/**
 * Shape of the shared Inertia page props returned by
 * `App\Http\Middleware\HandleInertiaRequests::share()`.
 */
import '@inertiajs/core';
import type { Activity, Category, Course, FormErrors, Result, Schedule, Trainer } from './domain';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    profile_picture: string | null;
    roles: string[];
    role?: string;
    email_verified_at?: string | null;
    is_admin: boolean;
    is_super_admin: boolean;
    is_trainer: boolean;
    permissions: string[];
}

export interface SharedFlash {
    success?: string | null;
    danger?: string | null;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
    };
    site: {
        logo: string;
    };
    ziggy: Record<string, unknown>;
    errors: FormErrors;
    flash: SharedFlash;
    courses?: Course[];
    categories?: Category[];
    activities?: Activity[];
    schedules?: Schedule[];
    trainers?: Trainer[];
    results?: Result[];
    [key: string]: any;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
