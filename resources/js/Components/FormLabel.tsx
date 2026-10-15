export interface FormLabelProps {
    /** Field name; also used to derive the `for` attribute when `id` is absent. */
    title?: string;
    /** Explicit element id the label points to. */
    id?: string;
    /** Label text; defaults to `title`. */
    label?: string | null;
    /** Renders the brand-colored required marker. */
    isRequired?: boolean;
    className?: string;
}

/** Shared form label with the `form-label` style and optional required marker. */
export default function FormLabel({
    title,
    id,
    label = null,
    isRequired = false,
    className = 'form-label',
}: FormLabelProps) {
    const htmlFor = id ?? (title ?? '').replace(/\s+/g, '_');

    return (
        <label htmlFor={htmlFor} className={className}>
            {label ?? title}
            {isRequired && <span className="text-[#A43B2F]"> *</span>}
        </label>
    );
}
