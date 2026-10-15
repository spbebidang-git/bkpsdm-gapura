import type { ChangeEvent, ReactNode } from 'react';
import ErrorMessage from '@/Components/ErrorMessage';
import FormLabel from '@/Components/FormLabel';
import useTransientFieldError from '@/Components/useTransientFieldError';

export interface FormSelectOption {
    value: string | number;
    label: string;
}

export interface FormSelectProps {
    /** Field name; also used to derive the `for`/`id` attribute when `id` is absent. */
    title?: string;
    /** Explicit element id; defaults to `title` slugified. */
    id?: string;
    /** Label text; defaults to `title`. */
    label?: string | null;
    /** Renders the brand-colored required marker next to the label. */
    isRequired?: boolean;
    /** Adds the native `required` attribute. */
    required?: boolean;
    /** Helper text shown below the control. */
    small?: string | null;
    /** Legacy alias for `value` (kept for backward compatibility). */
    selectedValue?: string | number | null;
    error?: string | null;
    disabled?: boolean;
    wrapperClassName?: string;
    className?: string;
    /** Simple option list; ignored when `children` is provided. */
    options?: FormSelectOption[];
    /** Label of the empty option; set to `null` to hide it. */
    emptyOption?: string | null;
    /** Fully custom `<option>` nodes (used by forms with grouped options). */
    children?: ReactNode;
    /** Inertia form value. */
    value: string | number | null | undefined;
    onChange: (event: ChangeEvent<HTMLSelectElement>) => void;
}

/** Shared select control with label, error message and empty option. */
export default function FormSelect({
    title,
    id,
    label = null,
    value,
    onChange,
    isRequired = false,
    required = false,
    small = null,
    selectedValue,
    options,
    emptyOption = 'Pilih Opsi',
    error = null,
    disabled = false,
    children,
    wrapperClassName = '',
    className = '',
}: FormSelectProps) {
    const controlLabel = label ?? title ?? id ?? '';
    const selectId = id ?? (title ?? '').replace(/\s+/g, '_');
    const { containerRef, visibleError, dismissError } = useTransientFieldError(error);

    return (
        <div ref={containerRef} className={wrapperClassName}>
            <FormLabel title={controlLabel} id={selectId} isRequired={isRequired} className={className || 'form-label'} />
            <select
                id={selectId}
                value={value ?? selectedValue ?? ''}
                onChange={(event) => { dismissError(); onChange(event); }}
                className={`form-control ${visibleError ? 'error' : ''}`.trim()}
                required={required}
                disabled={disabled}
                aria-invalid={Boolean(visibleError)}
            >
                {emptyOption !== null && <option value="">{emptyOption}</option>}
                {children ??
                    options?.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
            </select>
            {small && <p className="mt-1.5 text-xs leading-5 text-steel">{small}</p>}
            <ErrorMessage message={visibleError} />
        </div>
    );
}
