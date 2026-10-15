import { btnDanger, btnEdit, btnPrimarySm } from './ui';
import { Link } from '@inertiajs/react';
import { confirmDelete } from './ConfirmDelete';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import type { InertiaLinkProps } from '@inertiajs/react';

type LinkActionProps = Omit<InertiaLinkProps, 'href'> & { href: string; label?: string; icon?: string; className?: string; iconOnly?: boolean; children?: ReactNode };
type DeleteActionProps = Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'onClick'> & { url: string; message?: string; onSuccess?: () => void; label?: string; iconOnly?: boolean; className?: string };

/** Primary create/add CTA — page header */
export function CreateButton({
    href,
    label = 'Tambah',
    icon = 'bx-plus',
    className = '',
    ...props
}: LinkActionProps) {
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
export function EditButton({ href, label = 'Edit', icon = 'bx-edit-alt', iconOnly = false, className = '', ...props }: LinkActionProps) {
    return (
        <Link
            href={href}
            title={label}
            aria-label={label}
            className={`${btnEdit} ${className}`}
            {...props}
        >
            <i className={`bx ${icon} text-[15px] leading-none`} aria-hidden />
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
}: DeleteActionProps) {
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
    editLabel = 'Edit',
    editIcon = 'bx-edit-alt',
    deleteUrl,
    deleteMessage,
    onDeleted,
    iconOnly = false,
    className = '',
    variant = 'default',
}: { editHref?: string; editLabel?: string; editIcon?: string; deleteUrl?: string; deleteMessage?: string; onDeleted?: () => void; iconOnly?: boolean; className?: string; variant?: 'default' | 'course' }) {
    if (!editHref && !deleteUrl) return null;

    return (
        <div
            className={`inline-flex items-center justify-center gap-1.5 ${variant === 'course' ? 'admin-crud-row-actions-course' : ''} ${className}`}
            role="group"
            aria-label="Aksi baris"
        >
            {editHref ? <EditButton href={editHref} label={editLabel} icon={editIcon} iconOnly={iconOnly} /> : null}
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
