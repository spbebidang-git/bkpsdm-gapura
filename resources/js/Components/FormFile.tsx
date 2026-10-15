import type { ChangeEvent } from 'react';
import ErrorMessage from '@/Components/ErrorMessage';
import FormLabel from '@/Components/FormLabel';
import useTransientFieldError from '@/Components/useTransientFieldError';

export interface FormFileProps {
    /** Field name; also used to derive the `for`/`id` attribute. */
    title: string;
    /** Label text; defaults to `title`. */
    label?: string | null;
    isRequired?: boolean;
    error?: string | null;
    /** Object URL or static URL of the preview image. */
    previewUrl?: string | null;
    /** Whether a (new) file is currently selected. */
    hasFile?: boolean;
    /** Text shown over the preview when a new file is selected. */
    previewBadge?: string | null;
    /** Heading beside the preview. */
    heading: string;
    /** Helper paragraph beside the preview. */
    helperText: string;
    /** Text of the "choose file" button. */
    buttonLabel: string;
    accept?: string;
    variant?: 'default' | 'course';
    selectedFileName?: string | null;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}

/** Shared image upload field with preview card (used for course thumbnails). */
export default function FormFile({
    title,
    label = null,
    isRequired = false,
    error = null,
    previewUrl = null,
    hasFile = false,
    previewBadge = 'Pratinjau baru',
    heading,
    helperText,
    buttonLabel,
    accept = 'image/*',
    variant = 'default',
    selectedFileName = null,
    onChange,
}: FormFileProps) {
    const inputId = title.replace(/\s+/g, '_');
    const { containerRef, visibleError, dismissError } = useTransientFieldError(error);

    return (
        <div ref={containerRef} className={variant === 'course' ? 'course-media-image' : undefined}>
            <FormLabel
                title={label ?? title}
                id={inputId}
                isRequired={isRequired}
                className={`form-label ${visibleError ? '!text-red-700' : ''}`}
            />
            <div
                className={`course-thumbnail-field overflow-hidden rounded-xl border bg-surface-soft p-3 sm:p-4 ${variant === 'course' ? 'is-course-media' : ''} ${
                    visibleError ? 'is-invalid !border-red-600 !bg-red-50 ring-2 ring-red-500/30' : 'border-hairline'
                }`}
                aria-invalid={Boolean(visibleError)}
            >
                <div className={`flex flex-col gap-4 ${variant === 'course' ? '' : 'sm:flex-row sm:items-center'}`}>
                    <div className={`relative aspect-video w-full shrink-0 overflow-hidden rounded-lg border border-hairline bg-canvas ${variant === 'course' ? '' : 'sm:w-56'}`}>
                        {previewUrl ? (
                            <img src={previewUrl} alt={`Pratinjau ${label ?? title}`} className="h-full w-full object-cover" />
                        ) : (
                            <span className="flex h-full w-full flex-col items-center justify-center gap-1 text-steel">
                                <i className="bx bx-image-add text-3xl" aria-hidden />
                                <span className="text-xs">Pratinjau sampul akan muncul di sini</span>
                            </span>
                        )}
                        {(previewUrl || hasFile) && (
                            <span className="absolute bottom-2 left-2 rounded bg-black/60 px-2 py-1 text-[11px] font-medium text-white">
                                {previewBadge}
                            </span>
                        )}
                    </div>
                    <div className={`min-w-0 flex-1 ${variant === 'course' ? 'course-media-image-details' : ''}`}>
                        <div className="min-w-0">
                        <p className="text-sm font-semibold text-ink">{heading}</p>
                        <p className="mt-1 text-xs leading-5 text-steel">{helperText}</p>
                        {selectedFileName && <p className="course-media-filename" title={selectedFileName}><i className="bx bx-file" aria-hidden />{selectedFileName}</p>}
                        </div>
                        <label
                            htmlFor={inputId}
                            className="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-primary/25 bg-canvas px-3 py-2 text-xs font-semibold text-primary-pressed transition-colors hover:border-primary/50 hover:bg-primary-mist"
                        >
                            <i className="bx bx-image-add text-base" aria-hidden />
                            {buttonLabel}
                        </label>
                    </div>
                </div>
            </div>
            <input id={inputId} type="file" onChange={(event) => { dismissError(); onChange(event); }} className="sr-only" accept={accept} aria-invalid={Boolean(visibleError)} />
            <ErrorMessage message={visibleError} />
        </div>
    );
}
