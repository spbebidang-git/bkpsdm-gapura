import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import type { Dispatch, SetStateAction } from 'react';
import NotificationBell from '@/Components/Participant/NotificationBell';

const ROLE_LABELS: Record<string, string> = { super_admin: 'Super Admin', administrator: 'Administrator', trainer: 'Trainer', participant: 'Peserta' };
const roleLabel = (roles?: string[], fallback?: string) => roles?.map((role) => ROLE_LABELS[role]).find(Boolean) ?? fallback ?? 'Peserta';

const NAV = [
    { href: '/dashboard', label: 'Overview', icon: 'grid-alt', match: (url: string) => url === '/dashboard' },
    { href: '/my-course', label: 'Kelas Saya', icon: 'book-open', match: (url: string) => url.startsWith('/my-course') },
    { href: '/notifications', label: 'Notifikasi', icon: 'bell', match: (url: string) => url.startsWith('/notifications') },
    { href: '/profile', label: 'Profil', icon: 'id-card', match: (url: string) => url === '/profile' },
];

export default function Header({ isOpen, setIsOpen }: { isOpen: boolean; setIsOpen: Dispatch<SetStateAction<boolean>> }) {
    const { auth, site } = usePage().props;
    const { url } = usePage();
    const user = auth?.user;
    const participantName = user?.name?.trim().split(/\s+/)[0] || 'Peserta';
    const initial = (user?.name || 'P').trim().charAt(0).toUpperCase();
    const close = () => setIsOpen(false);
    const panelRef = useRef<HTMLDivElement>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const closeRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        const media = window.matchMedia('(min-width: 768px)');
        const closeOnDesktop = () => { if (media.matches) setIsOpen(false); };
        media.addEventListener('change', closeOnDesktop);
        closeOnDesktop();
        return () => media.removeEventListener('change', closeOnDesktop);
    }, [setIsOpen]);

    useEffect(() => {
        if (panelRef.current) panelRef.current.inert = !isOpen;
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen) return;
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') { setIsOpen(false); triggerRef.current?.focus(); }
        };
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);
        requestAnimationFrame(() => closeRef.current?.focus());
        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKey);
        };
    }, [isOpen, setIsOpen]);

    return <header className="fixed top-0 z-50 w-full border-t-[5px] border-[#4d5157] bg-[#EEF1EF]"><div className="mx-auto max-w-[1320px] bg-white/95 shadow-[0_10px_30px_rgba(15,61,62,0.07)] backdrop-blur-xl"><div className="bg-primary px-4 py-4 sm:px-7 md:px-10 md:py-5"><div className="flex items-center justify-between gap-4"><div className="flex min-w-0 items-center gap-4 sm:gap-7"><Link href="/dashboard" className="shrink-0 rounded-lg p-1 outline-none transition hover:opacity-80 focus-visible:ring-2 focus-visible:ring-primary/40"><img src={site.logo} alt="GAPURA" className="h-9 w-auto object-contain sm:h-11 md:h-12" /></Link><div className="min-w-0 border-l border-white/30 pl-4 sm:pl-7"><p className="truncate text-lg font-bold tracking-tight text-white sm:text-xl">Selamat datang, {participantName}</p><p className="mt-0.5 hidden text-sm text-white/80 sm:block">Lanjutkan pengembangan kompetensi Anda hari ini.</p></div></div><div className="flex shrink-0 items-center gap-2 sm:gap-3"><NotificationBell /><Link href={route('logout')} method="post" as="button" className="hidden h-10 w-10 place-items-center rounded-full bg-white shadow-[0_7px_16px_rgba(0,0,0,0.12)] transition hover:-translate-y-0.5 hover:bg-white/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 sm:h-12 sm:w-12 md:grid" aria-label="Keluar dari akun"><box-icon name="log-out" color="#018083" size="21px" aria-hidden="true" /></Link><button ref={triggerRef} onClick={() => setIsOpen((open) => !open)} type="button" className="grid h-10 w-10 place-items-center rounded-full border border-white/30 bg-white/15 transition hover:bg-white/25 md:hidden" aria-controls="participant-navigation" aria-expanded={isOpen}><span className="sr-only">Buka navigasi</span><box-icon name={isOpen ? 'x' : 'menu'} color="white" size="22px" aria-hidden="true" /></button></div></div></div><nav id="participant-tabs" aria-label="Navigasi peserta" className="hidden border-b border-[#d7e7ed] bg-white md:block"><ul className="mx-auto flex w-full max-w-[1320px] flex-col md:flex-row md:items-stretch md:justify-between"><ParticipantTab href="/dashboard" label="Overview" active={url === '/dashboard'} icon="grid-alt" /><ParticipantTab href="/my-course" label="Kelas Saya" active={url.startsWith('/my-course')} icon="book-open" /><ParticipantTab href="/profile" label="Profil" active={url === '/profile'} icon="id-card" /></ul></nav></div>

        <div className={`fixed inset-0 z-[70] md:hidden ${isOpen ? '' : 'pointer-events-none'}`} aria-hidden={!isOpen}>
            <button type="button" tabIndex={-1} aria-label="Tutup navigasi" onClick={close} className={`absolute inset-0 bg-[rgba(4,36,37,0.55)] backdrop-blur-sm transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'opacity-0'}`} />
            <div ref={panelRef} id="participant-navigation" role="dialog" aria-modal="true" aria-label="Menu navigasi peserta" className={`absolute right-0 top-0 flex h-full w-[min(86vw,344px)] flex-col bg-white shadow-[-12px_0_44px_rgba(4,36,37,0.28)] transition-transform duration-300 ease-out ${isOpen ? 'translate-x-0' : 'translate-x-full'}`}>
                <div className="bg-gradient-to-br from-[#019B91] to-[#015f61] px-5 pb-5 pt-5 text-white">
                    <div className="flex items-center justify-between gap-3">
                        <img src={site.logo} alt="GAPURA" className="h-8 w-auto object-contain" />
                        <button ref={closeRef} type="button" onClick={close} aria-label="Tutup navigasi" className="grid h-9 w-9 place-items-center rounded-full bg-white/15 transition hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"><box-icon name="x" color="white" size="22px" aria-hidden="true" /></button>
                    </div>
                    <div className="mt-5 flex items-center gap-3">
                        <span className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/20 font-poppins text-base font-semibold text-white" aria-hidden>{initial}</span>
                        <div className="min-w-0"><p className="truncate font-poppins text-sm font-semibold">{user?.name || 'Peserta'}</p><p className="truncate text-xs text-white/75">{roleLabel(user?.roles, user?.role)}</p></div>
                    </div>
                </div>

                <nav aria-label="Menu peserta" className="flex-1 overflow-y-auto overscroll-contain px-3 py-4">
                    <ul className="space-y-1">{NAV.map((item) => { const active = item.match(url); return <li key={item.href}><Link href={item.href} onClick={close} aria-current={active ? 'page' : undefined} className={`group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium outline-none transition focus-visible:ring-2 focus-visible:ring-[#018083]/40 ${active ? 'bg-[#F0FAF8] text-[#016668]' : 'text-[#526176] hover:bg-[#F5FBFA] hover:text-[#075D5E]'}`}><span className={`grid h-9 w-9 shrink-0 place-items-center rounded-[10px] transition ${active ? 'participant-icon-surface' : 'bg-[#F4F7FA] group-hover:bg-white'}`}><box-icon name={item.icon} color={active ? '#018083' : '#6b8799'} size="20px" aria-hidden="true" /></span><span className="truncate">{item.label}</span>{active && <span className="absolute right-3 h-5 w-1 rounded-full bg-gradient-to-b from-[#019b91] to-[#016668]" aria-hidden />}</Link></li>; })}</ul>
                </nav>

                <div className="mt-auto border-t border-[#E8ECF1] p-4">
                    <Link href={route('logout')} method="post" as="button" onClick={close} className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#FFF0F0] px-4 py-3 font-poppins text-sm font-semibold text-[#e74c3c] outline-none transition hover:bg-[#ffe4e4] focus-visible:ring-2 focus-visible:ring-[#e74c3c]/40"><box-icon name="log-out" color="#e74c3c" size="20px" aria-hidden="true" />Keluar dari akun</Link>
                </div>
            </div>
        </div>
    </header>;
}

function ParticipantTab({ href, label, active, icon }: { href: string; label: string; active: boolean; icon: string }) { return <li className="md:flex-1"><Link href={href} className={`relative flex items-center gap-2 px-5 py-3.5 text-sm font-medium outline-none transition hover:bg-[#f3fbfe] focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#168abd] md:justify-center md:py-4 md:text-base ${active ? 'font-bold text-[#045a7d]' : 'text-[#557083]'}`}><box-icon name={icon} color={active ? '#05729f' : '#6b8799'} size="19px" aria-hidden="true" />{label}<span className={`absolute bottom-0 left-0 h-[3px] w-full transition ${active ? 'bg-[#018083]' : 'bg-transparent'}`} /></Link></li>; }
