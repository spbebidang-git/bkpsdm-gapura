import type { ReactNode } from 'react';

export interface Breadcrumb { label: string; href?: string; }
export interface LayoutProps { children?: ReactNode; title?: string; breadcrumbs?: Breadcrumb[] | null; fullWidth?: boolean; }
