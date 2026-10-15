import { router } from '@inertiajs/react';
import { toast, type ToastContentProps } from 'react-toastify';

type ActivationNotice = {
    tone: 'success' | 'error' | 'warning';
    title: string;
    message: string;
    action?: { label: string; href: string };
};

function ActivationToast({ closeToast, data }: ToastContentProps<ActivationNotice>) {
    const { tone, title, message, action } = data;

    return <div className={`course-activation-notice is-${tone}`}>
        <span className="course-activation-notice__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                {tone === 'success' ? <path d="m5 12 4 4L19 6" /> : <><path d="M12 7v6" /><path d="M12 17h.01" /></>}
            </svg>
        </span>
        <div className="course-activation-notice__copy">
            <strong>{title}</strong>
            <p>{message}</p>
            {action && <button type="button" className="course-activation-notice__action" onClick={() => {
                closeToast();
                router.visit(action.href);
            }}>{action.label}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5" /></svg></button>}
        </div>
    </div>;
}

export function showCourseActivationToast(notice: ActivationNotice, id: string) {
    toast(ActivationToast, {
        data: notice,
        type: notice.tone,
        containerId: notice.tone === 'success' ? 'course-activation-success' : undefined,
        toastId: `course-activation-${id}`,
        className: `gapura-toast course-activation-toast course-activation-toast--${notice.tone}`,
        icon: false,
        closeOnClick: false,
        autoClose: notice.action ? 6500 : 5000,
        closeButton: ({ closeToast }) => <button type="button" className="course-activation-toast__close" onClick={closeToast} aria-label="Tutup notifikasi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg></button>,
    });
}
