import { Link } from '@inertiajs/react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

export function ParticipantHeading({ title, description, eyebrow, action }: { title: string; description?: string; eyebrow?: string; action?: ReactNode }) {
    return <div className="participant-heading flex flex-wrap items-end justify-between gap-4"><div className="min-w-0">{eyebrow && <p className="participant-eyebrow">{eyebrow}</p>}<h1>{title}</h1>{description && <p className="mt-2 text-sm leading-6 text-[#526176]">{description}</p>}</div>{action}</div>;
}

export function ParticipantCard({ children, className = '' }: { children: ReactNode; className?: string }) {
    return <section className={`participant-card ${className}`}>{children}</section>;
}

export function ParticipantButton({ children, href, variant = 'primary', className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { href?: string; variant?: 'primary' | 'secondary'; children: ReactNode }) {
    const classes = `participant-button participant-button-${variant} ${className}`;
    return href ? <Link href={href} className={classes}>{children}</Link> : <button type="button" {...props} className={classes}>{children}</button>;
}

export function ParticipantProgress({ value, label }: { value: number; label: string }) {
    const progress = Math.min(100, Math.max(0, Number(value) || 0));
    return <div className="h-2 overflow-hidden rounded-full bg-[#E6F3F3]" role="progressbar" aria-label={label} aria-valuemin={0} aria-valuemax={100} aria-valuenow={progress}><div className="h-full rounded-full bg-[linear-gradient(90deg,#019B91,#018083)]" style={{ width: `${progress}%` }} /></div>;
}

export function ParticipantEmpty({ title, description, action }: { title: string; description: string; action?: ReactNode }) {
    return <div className="rounded-[15px] border border-dashed border-[#D0D9E6] bg-[#F5FBFA] px-5 py-10 text-center"><span className="mx-auto grid h-12 w-12 place-items-center rounded-[10px] participant-icon-surface"><box-icon name="book-open" color="#018083" size="24px" aria-hidden="true" /></span><h3 className="mt-4 font-poppins text-base font-semibold text-[#075D5E]">{title}</h3><p className="mx-auto mt-2 max-w-md text-sm leading-6 text-[#526176]">{description}</p>{action && <div className="mt-5">{action}</div>}</div>;
}
