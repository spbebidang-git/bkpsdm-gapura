import { useEffect, useState } from 'react';

/** Shared flash payload shape (see HandleInertiaRequests::share()). */
interface FlashProp {
    success?: string | null;
    danger?: string | null;
}

type AlertMessage = { type: 'success' | 'danger'; message: string };

const getAlert = (flash: FlashProp = {}): AlertMessage | null => {
    if (flash.success) return { type: 'success', message: flash.success };
    if (flash.danger) return { type: 'danger', message: flash.danger };
    return null;
};

/** Shared inline feedback for every admin CRUD page. */
const AdminAlert = ({ initialFlash = {} }: { initialFlash?: FlashProp }) => {
    const [alert, setAlert] = useState<AlertMessage | null>(() => getAlert(initialFlash));

    // Same-page submissions preserve the layout, so read each new flash payload.
    useEffect(() => {
        setAlert(getAlert(initialFlash));
    }, [initialFlash]);

    useEffect(() => {
        if (!alert) return;
        const t = setTimeout(() => setAlert(null), 5000);
        return () => clearTimeout(t);
    }, [alert]);

    if (!alert) return null;

    const styles =
        alert.type === 'success'
            ? 'border-[#B9DDC5] bg-[#EAF7EE] text-[#246A3C]'
            : 'border-[#E9BEB8] bg-[#FFF4F1] text-[#A43B2F]';

    return (
        <div className={`mb-5 flex items-center justify-between gap-3 rounded-lg border px-4 py-3 text-sm font-medium shadow-[0_1px_0_rgba(0,0,0,0.03)] ${styles}`} role="alert">
            <span className="flex min-w-0 flex-1 items-center gap-2 leading-5">
                <i className={`bx ${alert.type === 'success' ? 'bx-check-circle' : 'bx-error-circle'} shrink-0 text-lg`} aria-hidden />
                <span className="min-w-0 break-words">{alert.message}</span>
            </span>
            <button type="button" onClick={() => setAlert(null)} className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md opacity-65 hover:bg-black/5 hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current" aria-label="Tutup pemberitahuan">
                <i className="bx bx-x text-lg" />
            </button>
        </div>
    );
};

export default AdminAlert;
