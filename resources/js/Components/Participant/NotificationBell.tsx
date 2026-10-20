import { Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import { createPortal } from 'react-dom';
import type { NotificationCountResponse, NotificationLatestResponse, ParticipantNotification } from '@/types/notification';

function csrfToken() {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function markRead(id: string) {
    const response = await fetch(`/notifications/${encodeURIComponent(id)}/read`, {
        method: 'PATCH', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
    });
    if (!response.ok) throw new Error('read');
}

export default function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [panelStyle, setPanelStyle] = useState<CSSProperties>({});
    const [items, setItems] = useState<ParticipantNotification[]>([]);
    const [count, setCount] = useState(0);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(false);
    const [refreshKey, setRefreshKey] = useState(0);
    const root = useRef<HTMLDivElement>(null);
    const button = useRef<HTMLButtonElement>(null);
    const panel = useRef<HTMLDivElement>(null);
    const busy = useRef(false);
    const delay = useRef(60_000);

    const refreshCount = useCallback(async (signal?: AbortSignal) => {
        if (document.visibilityState !== 'visible' || busy.current) return;
        busy.current = true;
        try {
            const response = await fetch('/notifications/count', { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal });
            if (!response.ok) throw new Error('count');
            const data = await response.json() as NotificationCountResponse;
            setCount(Number(data.unread_count) || 0); setError(false); return true;
        } catch (e) {
            if (!(e instanceof DOMException && e.name === 'AbortError')) setError(true); return false;
        } finally { busy.current = false; }
    }, []);

    const loadLatest = useCallback(async () => {
        setLoading(true); setError(false);
        try {
            const response = await fetch('/notifications/latest', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('latest');
            const data = await response.json() as NotificationLatestResponse;
            setItems(data.notifications ?? []); setCount(Number(data.unread_count) || 0);
        } catch { setError(true); }
        finally { setLoading(false); }
    }, []);

    useEffect(() => {
        let timer: number;
        const schedule = () => {
            window.clearTimeout(timer);
            if (document.visibilityState !== 'visible') return;
            timer = window.setTimeout(async () => {
                const succeeded = await refreshCount();
                delay.current = succeeded ? 60_000 : Math.min(delay.current * 2, 5 * 60_000);
                schedule();
            }, delay.current);
        };
        const onVisibility = () => { if (document.visibilityState === 'visible') refreshCount(); schedule(); };
        const onUpdated = () => { if (document.visibilityState === 'visible') refreshCount(); };
        refreshCount(); schedule(); document.addEventListener('visibilitychange', onVisibility);
        window.addEventListener('participant-notifications-updated', onUpdated);
        return () => { window.clearTimeout(timer); document.removeEventListener('visibilitychange', onVisibility); window.removeEventListener('participant-notifications-updated', onUpdated); };
    }, [refreshCount]);

    const positionPanel = useCallback(() => {
        const anchor = button.current?.getBoundingClientRect();
        if (!anchor) return;
        const width = Math.min(410, window.innerWidth - 24);
        const top = Math.min(anchor.bottom + 12, Math.max(12, window.innerHeight - 200));
        setPanelStyle({
            width,
            left: Math.max(12, Math.min(anchor.right - width, window.innerWidth - width - 12)),
            top,
            maxHeight: Math.max(0, window.innerHeight - top - 12),
        });
    }, []);

    useEffect(() => {
        if (!open) return;
        positionPanel();
        window.addEventListener('resize', positionPanel);
        window.addEventListener('scroll', positionPanel, true);
        return () => {
            window.removeEventListener('resize', positionPanel);
            window.removeEventListener('scroll', positionPanel, true);
        };
    }, [open, positionPanel]);

    useEffect(() => {
        if (!open) return;
        void loadLatest();
        const onPointer = (event: PointerEvent) => { if (!root.current?.contains(event.target as Node) && !panel.current?.contains(event.target as Node)) setOpen(false); };
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') { setOpen(false); button.current?.focus(); }
        };
        document.addEventListener('pointerdown', onPointer);
        document.addEventListener('keydown', onKey);
        requestAnimationFrame(() => panel.current?.querySelector<HTMLElement>('button, a')?.focus());
        return () => { document.removeEventListener('pointerdown', onPointer); document.removeEventListener('keydown', onKey); };
    }, [open, loadLatest, refreshKey]);

    const openItem = async (item: ParticipantNotification) => {
        try {
            await markRead(item.id);
            setItems(current => current.map(entry => entry.id === item.id ? { ...entry, read_at: new Date().toISOString() } : entry));
            setCount(current => Math.max(0, current - (item.read_at ? 0 : 1)));
        } catch { setError(true); }
        setOpen(false);
        if (item.action_url) router.visit(item.action_url);
    };

    const markAll = async () => {
        try {
            const response = await fetch('/notifications/read-all', { method: 'PATCH', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' } });
            if (!response.ok) throw new Error('read-all');
            setItems(current => current.map(item => ({ ...item, read_at: item.read_at ?? new Date().toISOString() }))); setCount(0);
            window.dispatchEvent(new Event('participant-notifications-updated'));
        } catch { setError(true); }
    };

    const refresh = () => setRefreshKey(value => value + 1);

    return <div className="relative" ref={root}>
        <button ref={button} type="button" onClick={() => { positionPanel(); setOpen(value => !value); }} aria-label={count ? `Notifikasi, ${count} belum dibaca` : 'Notifikasi'} aria-expanded={open} aria-controls="participant-notification-panel" aria-haspopup="dialog" className="relative grid h-10 w-10 place-items-center rounded-full border border-[#BDE8E2] bg-[linear-gradient(135deg,#FFFFFF,#DFF7F3)] shadow-[0_2px_8px_rgba(1,128,131,0.12)] transition hover:bg-[linear-gradient(135deg,#FFFFFF,#BDE8E2)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#018083] focus-visible:ring-offset-2 sm:h-12 sm:w-12">
            <box-icon name="bell" color="#018083" size="22px" aria-hidden="true" />
            {count > 0 && <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-[#E74C3C] px-1 text-center text-[10px] font-bold leading-5 text-white">{count > 99 ? '99+' : count}</span>}
        </button>
        {open && createPortal(<div id="participant-notification-panel" ref={panel} role="dialog" aria-label="Notifikasi" style={panelStyle} className="fixed z-[80] flex flex-col overflow-hidden rounded-2xl border border-[#E8ECF1] bg-white shadow-[0_18px_55px_rgba(15,61,62,0.2)]">
            <div className="flex shrink-0 flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-[#E8ECF1] bg-[#F5FBFA] px-4 py-3 sm:px-5"><div><h2 className="font-semibold text-[#075D5E]">Notifikasi</h2><p className="text-xs text-slate-500">{count ? `${count} belum dibaca` : 'Semua sudah dibaca'}</p></div><button type="button" onClick={() => { setOpen(false); button.current?.focus(); }} aria-label="Tutup notifikasi" className="grid h-11 w-11 shrink-0 place-items-center rounded-full text-xl text-slate-500 hover:bg-[#E6F3F3]">×</button>{count > 0 && <button type="button" onClick={markAll} className="min-h-11 text-left text-xs font-semibold text-[#018083] hover:underline">Tandai semua dibaca</button>}</div>
            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain" aria-live="polite">
                {loading && <p className="px-5 py-8 text-center text-sm text-slate-500">Memuat notifikasi…</p>}
                {!loading && error && <div className="px-5 py-8 text-center"><p className="text-sm text-slate-600">Notifikasi belum dapat dimuat.</p><button type="button" onClick={refresh} className="mt-2 text-sm font-semibold text-[#018083]">Coba lagi</button></div>}
                {!loading && !error && items.length === 0 && <p className="px-5 py-10 text-center text-sm text-slate-500">Belum ada notifikasi.</p>}
                {!loading && !error && items.map(item => <div key={item.id} className={`flex gap-3 border-b border-slate-100 px-4 py-3.5 last:border-0 ${item.read_at ? '' : 'bg-[#F5FBFA]'}`}>
                    <span className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full participant-icon-surface"><box-icon name={item.type === 'certificate' ? 'award' : item.type === 'deadline' || item.type === 'expired' ? 'time-five' : item.type === 'completed' ? 'check-circle' : 'info-circle'} color="#018083" size="18px" /></span>
                    <div className="min-w-0 flex-1 [overflow-wrap:anywhere]"><div className="flex items-start justify-between gap-2"><p className="text-sm font-semibold text-slate-800">{item.title}</p>{!item.read_at && <button type="button" aria-label="Tandai dibaca" onClick={async () => { try { await markRead(item.id); setItems(current => current.map(entry => entry.id === item.id ? { ...entry, read_at: new Date().toISOString() } : entry)); setCount(current => Math.max(0, current - 1)); } catch { setError(true); } }} className="grid h-8 w-8 shrink-0 place-items-center rounded-full after:h-2 after:w-2 after:rounded-full after:bg-[#019B91] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#018083]" />}</div><p className="mt-1 text-xs leading-5 text-slate-600">{item.message}</p>{item.deadline_at && <p className="mt-1 text-xs font-medium text-[#a35415]">Tenggat: {new Date(item.deadline_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' })} WIB</p>}<div className="mt-2 flex flex-wrap items-center justify-between gap-x-3 gap-y-1"><time className="text-[11px] text-slate-400">{new Date(item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' })}</time>{item.action_url && item.action_label && <button type="button" onClick={() => void openItem(item)} className="min-h-11 text-left text-xs font-semibold text-[#018083] hover:underline">{item.action_label}</button>}</div></div>
                </div>)}
            </div>
            <Link href="/notifications" onClick={() => setOpen(false)} className="block shrink-0 border-t border-slate-100 px-5 py-3 text-center text-sm font-semibold text-[#018083] hover:bg-[#E6F3F3]">Lihat semua notifikasi</Link>
        </div>, document.body)}
    </div>;
}
