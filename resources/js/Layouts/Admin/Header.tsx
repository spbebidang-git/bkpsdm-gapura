import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '@/types/layout';
import { roleLabel } from './navigation';

export default function Header({ title, breadcrumbs, actions, onOpenMenu }: {
    title: string;
    breadcrumbs: Breadcrumb[] | null;
    actions?: ReactNode;
    onOpenMenu: () => void;
}) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const trail = breadcrumbs ?? (title && title !== 'Admin' ? [{ label: title }] : []);

    return (
        <>
            <Head title={title} />
            <header className="admin-header">
                <button type="button" className="admin-chrome-icon lg:hidden" onClick={onOpenMenu} aria-label="Buka navigasi" aria-controls="admin-sidebar">
                    <i className="bx bx-menu" aria-hidden />
                </button>
                <nav aria-label="Breadcrumb" className="admin-breadcrumb">
                    <Link href={route('admin.dashboard')}>Admin</Link>
                    {trail.map((crumb, index) => (
                        <span key={`${crumb.label}-${index}`} className="admin-breadcrumb-item">
                            <i className="bx bx-chevron-right" aria-hidden />
                            {crumb.href && index !== trail.length - 1 ? (
                                <Link href={crumb.href}>{crumb.label}</Link>
                            ) : <span aria-current="page">{crumb.label}</span>}
                        </span>
                    ))}
                </nav>
                {actions && <div className="admin-header-actions">{actions}</div>}
                <div className="admin-header-account">
                    <span className="admin-avatar" aria-hidden>{(user?.name || 'A').charAt(0).toUpperCase()}</span>
                    <div className="admin-account-copy">
                        <span>{user?.name || 'Admin'}</span>
                        <small>{roleLabel(user?.roles, user?.role)}</small>
                    </div>
                    <Link href={route('logout')} method="post" as="button" aria-label="Keluar" title="Keluar" className="admin-chrome-icon admin-logout">
                        <i className="bx bx-log-out" aria-hidden />
                    </Link>
                </div>
            </header>
        </>
    );
}
