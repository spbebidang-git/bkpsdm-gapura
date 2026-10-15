import type { ChangeEvent } from 'react';
import ErrorMessage from '@/Components/ErrorMessage';
import FormLabel from '@/Components/FormLabel';
import useTransientFieldError from '@/Components/useTransientFieldError';

export interface FormFieldProps {
    /** Field name; also used to derive the `for`/`id` attribute when `id` is absent. */
    title?: string;
    /** Explicit element id; defaults to `title` slugified. */
    id?: string;
    /** Label text; defaults to `title`. */
    label?: string | null;
    isRequired?: boolean;
    error?: string | null;
    disabled?: boolean;
    readOnly?: boolean;
    placeholder?: string | null;
    /** Extra helper text shown below the control. */
    small?: string | null;
    wrapperClassName?: string;
    className?: string;
    /** Extra classes for the `<input>` element itself (e.g. `font-mono`). */
    inputClassName?: string;
    /** Inertia form value. */
    value: string | number | null | undefined;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    /** Input type, e.g. `text`, `number`, `url`, `password`. */
    type?: string;
    min?: string | number;
    step?: string | number;
    /** Adds the native `required` attribute (browser-level validation). */
    required?: boolean;
    /** Native `minLength` constraint. */
    minLength?: number;
    /** Native `autoComplete` hint (important for password fields). */
    autoComplete?: string;
}

const capitalize = (str: string): string => str.charAt(0).toUpperCase() + str.slice(1);

/** Shared text/number input with label, error message and helper text. */
export default function FormInput({
    title,
    id,
    label = null,
    value,
    onChange,
    isRequired = false,
    placeholder = null,
    error = null,
    small = null,
    type = 'text',
    min,
    step,
    required = false,
    minLength,
    autoComplete,
    disabled = false,
    readOnly = false,
    className = '',
    inputClassName = '',
    wrapperClassName = '',
}: FormFieldProps) {
    const controlLabel = label ?? title ?? id ?? '';
    const inputId = id ?? (title ?? '').replace(/\s+/g, '_');
    const finalPlaceholder = placeholder
        ? placeholder
        : capitalize(controlLabel);
    const { containerRef, visibleError, dismissError } = useTransientFieldError(error);

    return (
        <div ref={containerRef} className={wrapperClassName}>
            <FormLabel title={controlLabel} id={inputId} isRequired={isRequired} className={className || 'form-label'} />
            <input
                type={type}
                id={inputId}
                value={value ?? ''}
                min={min}
                step={step}
                onChange={(event) => { dismissError(); onChange(event); }}
                className={`form-control ${inputClassName} ${visibleError ? 'error' : ''} ${disabled || readOnly ? 'cursor-not-allowed' : ''}`.trim()}
                placeholder={finalPlaceholder}
                required={required}
                minLength={minLength}
                autoComplete={autoComplete}
                disabled={disabled}
                readOnly={readOnly}
                aria-invalid={Boolean(visibleError)}
            />
            {small && <p className="mt-1.5 text-xs leading-5 text-steel">{small}</p>}
            <ErrorMessage message={visibleError} />
        </div>
    );
}
