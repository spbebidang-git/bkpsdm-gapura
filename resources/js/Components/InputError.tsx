import type { HTMLAttributes } from 'react';
import { useEffect, useRef, useState } from 'react';

interface InputErrorProps extends HTMLAttributes<HTMLParagraphElement> { message?: string | string[] | null; }

export default function InputError({ message, className = '', ...props }: InputErrorProps) {
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

    return message && !edited ? (
        <p ref={errorRef} {...props} data-field-validation-error className={'text-sm text-red-600 dark:text-red-400 ' + className}>
            {Array.isArray(message) ? message.join(', ') : message}
        </p>
    ) : null;
}
