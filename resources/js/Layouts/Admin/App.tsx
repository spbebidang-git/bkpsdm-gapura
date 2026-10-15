import AdminAlert from '@/Components/Admin/AdminAlert';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '@/types/layout';
import Header from './Header';
import Sidebar from './Sidebar';
import Footer from './Footer';
import '../../../css/admin-layout.css';

export interface AdminLayoutProps {
    title?: string;
    breadcrumbs?: Breadcrumb[] | null;
    hideTitle?: boolean;
    actions?: ReactNode;
    fullWidth?: boolean;
    children?: ReactNode;
}

export default function App({ title = 'Admin', breadcrumbs = null, hideTitle = false, actions = null, fullWidth = false, children }: AdminLayoutProps) {
    const [menuOpen, setMenuOpen] = useState(false);
    const bodyRef = useRef<HTMLDivElement>(null);
    const { flash } = usePage().props;

    useEffect(() => {
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setMenuOpen(false);
        };
        const media = window.matchMedia('(min-width: 1024px)');
        const closeOnDesktop = () => { if (media.matches) setMenuOpen(false); };
        window.addEventListener('keydown', closeOnEscape);
        media.addEventListener('change', closeOnDesktop);
        return () => {
            window.removeEventListener('keydown', closeOnEscape);
            media.removeEventListener('change', closeOnDesktop);
        };
    }, []);

    useEffect(() => {
        if (bodyRef.current) bodyRef.current.inert = menuOpen;
        if (!menuOpen) return;
        const previousOverflow = document.body.style.overflow;
        const previousFocus = document.activeElement as HTMLElement | null;
        document.body.style.overflow = 'hidden';
        const sidebar = document.getElementById('admin-sidebar');
        const focusable = () => Array.from(sidebar?.querySelectorAll<HTMLElement>('a[href], button:not([disabled])') ?? []).filter((element) => element.getClientRects().length > 0);
        focusable()[0]?.focus();
        const keepFocusInside = (event: KeyboardEvent) => {
            if (event.key !== 'Tab') return;
            const elements = focusable();
            const first = elements[0];
            const last = elements[elements.length - 1];
            if (!first) return;
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        };
        window.addEventListener('keydown', keepFocusInside);
        return () => {
            if (bodyRef.current) bodyRef.current.inert = false;
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', keepFocusInside);
            previousFocus?.focus();
        };
    }, [menuOpen]);

    return (
        <div className="admin-shell admin-layout min-h-screen">
            <Sidebar open={menuOpen} onClose={() => setMenuOpen(false)} />
            <div ref={bodyRef} className="admin-layout-body flex min-h-screen flex-col">
                <Header title={title} breadcrumbs={breadcrumbs} actions={actions} onOpenMenu={() => setMenuOpen(true)} />
                <main className="admin-main w-full flex-1">
                    <div className={fullWidth ? 'w-full' : 'mx-auto w-full max-w-[1540px]'}>
                        <AdminAlert initialFlash={flash ?? {}} />
                        {!hideTitle && title && <div className="mb-6"><h1 className="admin-layout-title">{title}</h1></div>}
                        {children}
                    </div>
                </main>
                <Footer />
            </div>
        </div>
    );
}
