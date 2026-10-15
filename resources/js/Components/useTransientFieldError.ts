import { useEffect, useRef, useState } from 'react';

/** Hide a server error after the field is edited, then show it again on the next submit. */
export default function useTransientFieldError(error?: string | null) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [edited, setEdited] = useState(false);

    useEffect(() => setEdited(false), [error]);

    useEffect(() => {
        const form = containerRef.current?.closest('form');
        if (!form) return;
        const reset = () => setEdited(false);
        form.addEventListener('submit', reset);
        return () => form.removeEventListener('submit', reset);
    }, []);

    return {
        containerRef,
        visibleError: edited ? null : error,
        dismissError: () => setEdited(true),
    };
}
