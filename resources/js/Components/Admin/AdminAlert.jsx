import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** Inline admin feedback. It stays close to the page that triggered it. */
const AdminAlert = ({ initialFlash = {} }) => {
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        const show = (flash = {}) => {
            if (flash.success) {
                setAlert({ type: 'success', message: flash.success });
            } else if (flash.danger) {
                setAlert({ type: 'danger', message: flash.danger });
            }
        };

        show(initialFlash);

        const removeListener = router.on('success', (event) => {
            show(event.detail.page.props.flash ?? {});
        });

        return () => removeListener();
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
                <span>{alert.message}</span>
            </span>
            <button type="button" onClick={() => setAlert(null)} className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md opacity-65 hover:bg-black/5 hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current" aria-label="Tutup pemberitahuan">
                <i className="bx bx-x text-lg" />
            </button>
        </div>
    );
};

export default AdminAlert;
