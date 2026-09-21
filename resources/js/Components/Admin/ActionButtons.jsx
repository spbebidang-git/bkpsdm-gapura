import { btnDanger, btnEdit, btnPrimarySm } from './ui';
import { Link } from '@inertiajs/react';
import { confirmDelete } from './ConfirmDelete';

/** Primary create/add CTA — page header */
export function CreateButton({
    href,
    label = 'Tambah',
    icon = 'bx-plus',
    className = '',
    ...props
}) {
    return (
        <Link
            href={href}
            title={label}
            aria-label={label}
            className={`${btnPrimarySm} ${className}`}
            {...props}
        >
            <i className={`bx ${icon} text-base leading-none`} aria-hidden />
            <span>{label}</span>
        </Link>
    );
}

/** Edit link — table row action */
export function EditButton({ href, label = 'Edit', iconOnly = false, className = '', ...props }) {
    return (
        <Link
            href={href}
            title={label}
            aria-label={label}
            className={`${btnEdit} ${className}`}
            {...props}
        >
            <i className="bx bx-edit-alt text-[15px] leading-none" aria-hidden />
            {!iconOnly && <span>{label}</span>}
        </Link>
    );
}

/** Delete button — wires confirmDelete */
export function DeleteButton({
    url,
    message,
    onSuccess,
    label = 'Hapus',
    iconOnly = false,
    className = '',
    ...props
}) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            onClick={() => confirmDelete(url, { message, onSuccess })}
            className={`${btnDanger} ${className}`}
            {...props}
        >
            <i className="bx bx-trash text-[15px] leading-none" aria-hidden />
            {!iconOnly && <span>{label}</span>}
        </button>
    );
}

/** Edit + delete group for DataTable rows */
export function RowActions({
    editHref,
    deleteUrl,
    deleteMessage,
    onDeleted,
    iconOnly = false,
    className = '',
}) {
    if (!editHref && !deleteUrl) return null;

    return (
        <div
            className={`inline-flex items-center justify-center gap-1.5 ${className}`}
            role="group"
            aria-label="Aksi baris"
        >
            {editHref ? <EditButton href={editHref} iconOnly={iconOnly} /> : null}
            {deleteUrl ? (
                <DeleteButton
                    url={deleteUrl}
                    message={deleteMessage}
                    onSuccess={onDeleted}
                    iconOnly={iconOnly}
                />
            ) : null}
        </div>
    );
}
