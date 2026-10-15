import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { cssTransition, toast } from 'react-toastify';

export const GapuraToastTransition = cssTransition({ enter: 'gapura-toast-enter', exit: 'gapura-toast-exit', appendPosition: true, collapse: true });

function FlashToast({ title, message, tone }: { title: string; message: string; tone: 'success' | 'error' }) {
    return <div className={`gapura-toast-content is-${tone}`}>
        <span className="gapura-toast-icon" aria-hidden><i className={`bx ${tone === 'success' ? 'bx-check' : 'bx-error'}`} /></span>
        <span className="gapura-toast-copy"><strong>{title}</strong><span>{message}</span></span>
    </div>;
}

interface FlashProp {
    success?: string | null;
    danger?: string | null;
}

const showFlashToast = (flash: FlashProp = {}, component: string) => {
    // Admin CRUD feedback is rendered inline by AdminAlert.
    if (component.startsWith('Admin/')) return;

    if (flash.success) {
        toast.success(<FlashToast title="Berhasil" message={flash.success} tone="success" />, {
            position: 'top-center',
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
            theme: 'light',
            transition: GapuraToastTransition,
            className: 'gapura-toast gapura-toast-success',
            icon: false,
        });
    }

    if (flash.danger) {
        toast.error(<FlashToast title="Terjadi kesalahan" message={flash.danger} tone="error" />, {
            position: 'top-center',
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
            theme: 'light',
            transition: GapuraToastTransition,
            className: 'gapura-toast gapura-toast-error',
            icon: false,
        });
    }
};

const FlashMessageToast = ({ initialFlash = {}, initialComponent }: { initialFlash?: FlashProp; initialComponent: string }) => {
    useEffect(() => {
        showFlashToast(initialFlash, initialComponent);

        const removeSuccessListener = router.on('success', (event) => {
            showFlashToast(event.detail.page.props.flash ?? {}, event.detail.page.component);
        });

        return () => {
            removeSuccessListener();
        };
    }, [initialFlash, initialComponent]);

    return null;
};

export default FlashMessageToast;
