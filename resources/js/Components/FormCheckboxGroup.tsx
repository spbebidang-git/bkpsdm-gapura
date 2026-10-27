import type { ChangeEvent } from 'react';
import useTransientFieldError from '@/Components/useTransientFieldError';

/** Brand-styled alert used for group-level validation errors. */
export const checkboxGroupErrorClass =
    'mt-3 flex items-center gap-2 rounded-lg border border-[#E9BEB8] bg-[#FFF4F1] px-3 py-2 text-xs font-semibold text-[#A43B2F]';

export interface FormCheckboxGroupOption {
    id: string | number;
    title: string;
}

export interface FormCheckboxGroupProps {
    /** Group label. */
    label: string;
    /** Field name the values are collected under (e.g. `employee_status`). */
    name: string;
    /** Available options. */
    options: FormCheckboxGroupOption[];
    /** Currently selected ids. */
    values: Array<string | number>;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    error?: string | null;
    /** Optional description below the label. */
    description?: string | null;
    /** Text shown when `options` is empty. */
    emptyText?: string | null;
    /** Render every option, or only when `showEmpty` is true. */
    columns?: 2 | 3;
}

/** Shared checkbox-grid group (used for multi-select toggles like Status Pengguna). */
export default function FormCheckboxGroup({
    label,
    name,
    options,
    values,
    onChange,
    error = null,
    description = null,
    emptyText = null,
    columns = 2,
}: FormCheckboxGroupProps) {
    const columnClass = columns === 3 ? 'grid-cols-3' : 'grid-cols-2';
    const { containerRef, visibleError, dismissError } = useTransientFieldError(error);

    return (
        <div ref={containerRef}>
            <span className={`form-label ${visibleError ? '!text-[#A43B2F]' : ''}`}>{label}</span>
            {description && <p className="mb-3 text-xs leading-5 text-steel">{description}</p>}
            {options.length === 0 && emptyText && <p className="text-sm text-steel">{emptyText}</p>}
            <div
                className={`grid ${columnClass} gap-2 rounded-xl border p-2 ${visibleError ? 'border-[#B75346] bg-[#FFF9F7]' : 'border-transparent'}`}
                aria-invalid={Boolean(visibleError)}
            >
                {options.map((option) => {
                    const isChecked = values.some((value) => String(value) === String(option.id));
                    return (
                        <label
                            key={option.id}
                            className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors ${
                                isChecked
                                    ? 'border-primary/45 bg-primary-mist text-primary-pressed'
                                    : 'border-hairline bg-canvas text-charcoal hover:border-primary/30'
                            }`}
                        >
                            <input
                                type="checkbox"
                                id={`${name}_${option.id}`}
                                name={name}
                                value={option.id}
                                checked={isChecked}
                                onChange={(event) => { dismissError(); onChange(event); }}
                                className="h-4 w-4 rounded border-hairline text-primary focus:ring-primary"
                            />
                            <span className="truncate">{option.title}</span>
                        </label>
                    );
                })}
            </div>
            {visibleError && (
                <p role="alert" className={checkboxGroupErrorClass}>
                    <i className="bx bx-error-circle text-base" aria-hidden />
                    {visibleError}
                </p>
            )}
        </div>
    );
}
