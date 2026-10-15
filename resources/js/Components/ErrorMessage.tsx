import { fieldError } from '@/Components/Admin/ui';
import { useEffect, useRef, useState } from 'react';

export interface ErrorMessageProps {
    message?: string | null;
    /** Overrides the default brand error styling. */
    className?: string;
}

/** Inline field error message styled with the shared brand token. */
export default function ErrorMessage({ message, className = fieldError }: ErrorMessageProps) {
    const errorRef = useRef<HTMLParagraphElement>(null);
    const [edited, setEdited] = useState(false);

    useEffect(() => {
        setEdited(false);
        if (!message) return;

        const container = errorRef.current?.parentElement;
        const form = container?.closest('form');
        const dismiss = () => setEdited(true);
        const reset = () => setEdited(false);
        container?.addEventListener('input', dismiss);
        container?.addEventListener('change', dismiss);
        form?.addEventListener('submit', reset);

        return () => {
            container?.removeEventListener('input', dismiss);
            container?.removeEventListener('change', dismiss);
            form?.removeEventListener('submit', reset);
        };
    }, [message]);

    if (!message || edited) {
        return null;
    }

    return <p ref={errorRef} className={className}>{message}</p>;
}
