import type { ChangeEvent, ReactNode } from 'react';
import useTransientFieldError from '@/Components/useTransientFieldError';

export default function FormCheckbox({ id, title, checked, onChange, error = null, value }: { id?: string; title?: ReactNode; checked?: boolean; onChange?: (event: ChangeEvent<HTMLInputElement>) => void; error?: string | null; value?: string | number | boolean }) {
    const { containerRef, visibleError, dismissError } = useTransientFieldError(error);
    return (
        <div ref={containerRef} className="flex items-center mb-2">
            <input
                id={id}
                type="checkbox"
                checked={checked}
                value={value as string | number | readonly string[] | undefined}
                onChange={(event) => { dismissError(); onChange?.(event); }}
                className="h-4 w-4 rounded border-hairline bg-white text-primary focus:ring-2 focus:ring-primary/30"
            />
            <label
                htmlFor={id}
                className="ms-2 text-sm font-medium text-charcoal"
            >
                {title}
            </label>
            {visibleError && <p className="ml-2 text-sm text-red-600">{visibleError}</p>}
        </div>
    );
}
