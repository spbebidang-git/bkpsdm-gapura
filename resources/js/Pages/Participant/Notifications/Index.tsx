import App from '@/Layouts/Participant/App';
import { Link, router, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/types/inertia';
import { useState } from 'react';
import type { PaginatedNotifications } from '@/types/notification';

export default function NotificationsIndex() {
    const { notifications, filter, unreadCount } = usePage<SharedProps & { notifications: PaginatedNotifications; filter: 'all' | 'unread'; unreadCount: number }>().props;
    const [busy, setBusy] = useState<string | null>(null);
    const token = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const read = async (id: string, reload = true) => {
        setBusy(id);
        try {
            const response = await fetch(`/notifications/${encodeURIComponent(id)}/read`, { method: 'PATCH', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': token(), Accept: 'application/json' } });
            if (!response.ok) throw new Error('read');
            if (reload) router.reload({ only: ['notifications', 'unreadCount'] });
            window.dispatchEvent(new Event('participant-notifications-updated'));
        } finally { setBusy(null); }
    };
    const readAll = async () => {
        setBusy('all');
        try {
            const response = await fetch('/notifications/read-all', { method: 'PATCH', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': token(), Accept: 'application/json' } });
            if (!response.ok) throw new Error('read-all');
            router.reload({ only: ['notifications', 'unreadCount'] });
            window.dispatchEvent(new Event('participant-notifications-updated'));
        } finally { setBusy(null); }
    };

    return <App><div className="mx-auto max-w-4xl">
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4"><div><p className="text-sm font-medium text-[#019B91]">Pembaruan kelas Anda</p><h1 className="mt-1 text-3xl font-bold text-[#075D5E]">Notifikasi</h1><p className="mt-2 text-sm text-slate-600">{unreadCount} notifikasi belum dibaca</p></div>{unreadCount > 0 && <button type="button" disabled={busy === 'all'} onClick={() => void readAll()} className="rounded-xl border border-[#BDE8E2] bg-white px-4 py-2 text-sm font-semibold text-[#018083] disabled:opacity-50">Tandai semua dibaca</button>}</div>
        <div className="mb-4 flex gap-2"><Link href="/notifications?filter=all" className={`rounded-full px-4 py-2 text-sm font-semibold ${filter === 'all' ? 'bg-[#018083] text-white' : 'bg-white text-slate-600'}`}>Semua</Link><Link href="/notifications?filter=unread" className={`rounded-full px-4 py-2 text-sm font-semibold ${filter === 'unread' ? 'bg-[#018083] text-white' : 'bg-white text-slate-600'}`}>Belum dibaca</Link></div>
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {notifications.data.length === 0 ? <div className="px-6 py-16 text-center text-sm text-slate-500">Belum ada notifikasi pada filter ini.</div> : notifications.data.map(item => <article key={item.id} className={`flex flex-col gap-4 border-b border-slate-100 px-5 py-5 last:border-0 sm:flex-row sm:items-start ${item.read_at ? '' : 'bg-[#F5FBFA]'}`}>
                <span className="hidden h-2 w-2 shrink-0 rounded-full bg-[#019B91] sm:mt-2 sm:block" aria-hidden="true" />
                <div className="min-w-0 flex-1"><h2 className="font-semibold text-slate-800">{item.title}</h2><p className="mt-1 text-sm leading-6 text-slate-600">{item.message}</p>{item.deadline_at && <p className="mt-1 text-xs font-medium text-[#a35415]">Tenggat: {new Date(item.deadline_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' })} WIB</p>}<time className="mt-2 block text-xs text-slate-400">{new Date(item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' })}</time></div>
                <div className="flex shrink-0 flex-wrap items-center gap-3">{!item.read_at && <button type="button" disabled={busy === item.id} onClick={() => void read(item.id)} className="text-xs font-medium text-slate-500 hover:text-slate-800">Tandai dibaca</button>}{item.action_url && item.action_label && <button type="button" onClick={async () => { if (!item.read_at) await read(item.id, false); router.visit(item.action_url!); }} className="rounded-lg bg-[#E6F3F3] px-3 py-2 text-xs font-semibold text-[#018083] hover:bg-[#BDE8E2]">{item.action_label}</button>}</div>
            </article>)}
        </div>
        {notifications.last_page > 1 && <nav aria-label="Halaman notifikasi" className="mt-5 flex flex-wrap justify-center gap-2">{notifications.links.map((link, i) => link.url ? <Link key={i} href={link.url} preserveScroll className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-[#018083] text-white' : 'bg-white text-slate-600'}`} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span key={i} className="px-3 py-2 text-sm text-slate-300" dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>}
    </div></App>;
}
