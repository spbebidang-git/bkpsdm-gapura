import '@/../css/admin-literature-file.css';
import { useRef } from 'react';

/**
 * Shared PDF dropzone for literature activities.
 * Mirrors the participant "tugas" upload styling so admin and learn share one theme.
 */
export default function LiteratureFileField({
    file,
    error,
    existingUrl = null,
    existingName = null,
    isRequired = false,
    disabled = false,
    maxSizeMB = 5,
    onChange,
    onDismissError,
}: {
    file: File | null;
    error?: string | null;
    existingUrl?: string | null;
    existingName?: string | null;
    isRequired?: boolean;
    disabled?: boolean;
    maxSizeMB?: number;
    onChange: (file: File | null) => void;
    onDismissError?: () => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);

    const clear = () => {
        onDismissError?.();
        onChange(null);
        if (inputRef.current) inputRef.current.value = '';
    };

    const filled = Boolean(file);

    return (
        <div className="literature-file">
            <label
                htmlFor="content"
                className={`literature-file__dropzone${error ? ' is-invalid' : ''}${filled ? ' is-filled' : ''}${disabled ? ' is-disabled' : ''}`}
            >
                <span className="literature-file__icon" aria-hidden>
                    <i className={`bx ${filled ? 'bx-check-circle' : 'bx-cloud-upload'}`} />
                </span>
                {filled ? (
                    <>
                        <p className="literature-file__drop-title">File siap diunggah</p>
                        <p className="literature-file__drop-hint">Klik untuk mengganti file</p>
                    </>
                ) : (
                    <>
                        <p className="literature-file__drop-title"><span>Klik untuk unggah</span> PDF materi</p>
                        <p className="literature-file__drop-hint">Hanya format PDF · Maksimum {maxSizeMB}MB</p>
                    </>
                )}
                <input
                    ref={inputRef}
                    id="content"
                    type="file"
                    accept="application/pdf"
                    className="hidden"
                    disabled={disabled}
                    onChange={(event) => {
                        onDismissError?.();
                        onChange(event.target.files?.[0] ?? null);
                    }}
                />
            </label>

            {filled && file && (
                <p className="literature-file__meta">
                    <i className="bx bxs-file-pdf" aria-hidden />
                    <strong>{file.name}</strong>
                    <span>{(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    <button type="button" className="literature-file__remove" onClick={clear} disabled={disabled} aria-label="Batalkan pilihan file">
                        <i className="bx bx-x" aria-hidden />
                    </button>
                </p>
            )}

            {!filled && existingUrl && (
                <p className="literature-file__meta is-existing">
                    <i className="bx bxs-file-pdf" aria-hidden />
                    <strong>{existingName || 'Berkas saat ini'}</strong>
                    <a href={existingUrl} target="_blank" rel="noopener noreferrer" className="literature-file__link">Lihat PDF</a>
                </p>
            )}

            {error && (
                <div className="literature-file__alert" role="alert" aria-live="assertive">
                    <span className="literature-file__alert-icon" aria-hidden><i className="bx bx-error-circle" /></span>
                    <div className="literature-file__alert-copy">
                        <strong>Berkas belum valid</strong>
                        <span>{error}</span>
                    </div>
                </div>
            )}

            {isRequired && !filled && !existingUrl && (
                <p className="literature-file__hint">Berkas PDF wajib diunggah untuk jenis kegiatan literatur.</p>
            )}
        </div>
    );
}
