import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { navigation, isActive, trainerRoutes, roleLabel } from './navigation';

export default function Sidebar({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [desktop, setDesktop] = useState(false);
    const sidebarRef = useRef<HTMLElement>(null);
    useEffect(() => {
        if (sidebarRef.current) sidebarRef.current.inert = !open && !desktop;
    }, [open, desktop]);
    useEffect(() => {
        const media = window.matchMedia('(min-width: 1024px)');
        const update = () => setDesktop(media.matches);
        update();
        media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);
    const canSee = (item: any) => user?.is_super_admin || user?.permissions?.includes(item.permission) || (!item.permission && trainerRoutes.has(item.route));
    const groups = navigation.map((group) => ({ ...group, items: group.items.filter(canSee) })).filter((group) => group.items.length > 0);

    return (
        <>
            {open && <button type="button" aria-label="Tutup navigasi" className="admin-sidebar-overlay lg:hidden" onClick={onClose} />}
            <aside ref={sidebarRef} id="admin-sidebar" aria-label="Navigasi admin" className={`admin-sidebar ${open ? 'is-open' : ''}`}>
                <div className="admin-sidebar-brand">
                    <Link href={route('admin.dashboard')} aria-label="Dashboard GAPURA">
                        <img src="/assets/media/images/static/site_logo.png" alt="BKPSDM Kabupaten Gresik" />
                    </Link>
                    <button type="button" aria-label="Tutup navigasi" className="admin-chrome-icon lg:hidden" onClick={onClose}>
                        <i className="bx bx-x" aria-hidden />
                    </button>
                </div>
                <nav className="admin-nav admin-sidebar-nav">
                    {groups.map((group) => (
                        <section key={group.label}>
                            {!group.hideLabel && <h2>{group.label}</h2>}
                            <div className="space-y-1">
                                {group.items.map((item) => {
                                    const active = isActive(item.match);
                                    return (
                                        <Link key={item.route} href={route(item.route)} onClick={onClose} aria-current={active ? 'page' : undefined} className={`admin-sidebar-link ${active ? 'is-active' : ''}`}>
                                            <i className={`bx ${item.icon}`} aria-hidden />
                                            <span>{item.label}</span>
                                        </Link>
                                    );
                                })}
                            </div>
                        </section>
                    ))}
                </nav>
                <div className="admin-sidebar-account lg:hidden">
                    <span className="admin-avatar" aria-hidden>{(user?.name || 'A').charAt(0).toUpperCase()}</span>
                    <div className="admin-account-copy"><span>{user?.name || 'Admin'}</span><small>{roleLabel(user?.roles, user?.role)}</small></div>
                    <Link href={route('logout')} method="post" as="button" aria-label="Keluar" title="Keluar" className="admin-chrome-icon admin-logout"><i className="bx bx-log-out" aria-hidden /></Link>
                </div>
            </aside>
        </>
    );
}
