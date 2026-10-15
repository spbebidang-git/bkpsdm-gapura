import type { ChangeEvent } from 'react';
import ErrorMessage from '@/Components/ErrorMessage';
import FormLabel from '@/Components/FormLabel';
import useTransientFieldError from '@/Components/useTransientFieldError';

export interface FormTextAreaProps {
    /** Field name; also used to derive the `for`/`id` attribute when `id` is absent. */
    title?: string;
    /** Explicit element id; defaults to `title` slugified. */
    id?: string;
    /** Label text; defaults to `title`. */
    label?: string | null;
    isRequired?: boolean;
    error?: string | null;
    disabled?: boolean;
    /** Adds the native `required` attribute. */
    required?: boolean;
    rows?: number;
    placeholder?: string | null;
    small?: string | null;
    wrapperClassName?: string;
    className?: string;
    /** Extra classes for the `<textarea>` element itself. */
    inputClassName?: string;
    /** Inertia form value. */
    value: string | null | undefined;
    onChange: (event: ChangeEvent<HTMLTextAreaElement>) => void;
}

/** Shared textarea with label, error message and helper text. */
export default function FormTextArea({
    title,
    id,
    label = null,
    value,
    onChange,
    isRequired = false,
    error = null,
    disabled = false,
    required = false,
    rows = 3,
    placeholder = null,
    small = null,
    wrapperClassName = '',
    className = '',
    inputClassName = '',
}: FormTextAreaProps) {
    const controlLabel = label ?? title ?? id ?? '';
    const areaId = id ?? (title ?? '').replace(/\s+/g, '_');
    const finalPlaceholder = placeholder ?? `Masukkan ${controlLabel}`;
    const { containerRef, visibleError, dismissError } = useTransientFieldError(error);

    return (
        <div ref={containerRef} className={wrapperClassName}>
            <FormLabel title={controlLabel} id={areaId} isRequired={isRequired} className={className || 'form-label'} />
            <textarea
                id={areaId}
                rows={rows}
                placeholder={finalPlaceholder}
                className={`form-control ${inputClassName} ${visibleError ? 'error' : ''}`.trim()}
                value={value ?? ''}
                onChange={(event) => { dismissError(); onChange(event); }}
                disabled={disabled}
                required={required}
                aria-invalid={Boolean(visibleError)}
            />
            {small && <p className="mt-1.5 text-xs leading-5 text-steel">{small}</p>}
            <ErrorMessage message={visibleError} />
        </div>
    );
}
