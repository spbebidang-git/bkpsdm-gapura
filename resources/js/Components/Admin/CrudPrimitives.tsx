import type { ReactNode } from 'react';
import '@/../css/admin-crud.css';

/** Shared page heading used by admin CRUD indexes and forms. */
export function CrudPageHeader({ title, description, actions, className = '' }: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    className?: string;
}) {
    return <header className={`admin-crud-header ${className}`}>
        <div className="min-w-0"><h1>{title}</h1>{description && <p>{description}</p>}</div>
        {actions && <div className="admin-crud-header-actions">{actions}</div>}
    </header>;
}

/** Shared card section for admin CRUD forms. */
export function CrudFormSection({ title, description, icon, children, className = '' }: {
    title: string;
    description?: string;
    icon?: string;
    children: ReactNode;
    className?: string;
}) {
    return <section className={`admin-crud-form-card course-form-card ${className}`}>
        <header className="admin-crud-section-header course-form-section-header">
            {icon && <span className="admin-crud-section-icon course-form-section-icon"><i className={`bx ${icon}`} aria-hidden /></span>}
            <div><h2>{title}</h2>{description && <p>{description}</p>}</div>
        </header>
        <div className="admin-crud-section-body course-form-section-body">{children}</div>
    </section>;
}

/** Compact status badge following GAPURA semantic colors. */
export function CrudStatusBadge({ active, activeLabel = 'Aktif', inactiveLabel = 'Nonaktif' }: {
    active: boolean;
    activeLabel?: string;
    inactiveLabel?: string;
}) {
    return <span className={`admin-crud-status ${active ? 'is-active' : 'is-inactive'}`}>
        <span aria-hidden />{active ? activeLabel : inactiveLabel}
    </span>;
}
