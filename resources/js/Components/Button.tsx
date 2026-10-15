import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

/**
 * GAPURA reusable button primitive.
 *
 * Variants : primary | secondary | warning | danger | success
 * Sizes    : md (default) | lg | sm | xs
 *
 * Renders an Inertia <Link> when `href` is given, otherwise a <button>.
 *
 * Examples:
 *   <Button variant="primary">Simpan</Button>
 *   <Button variant="danger" size="sm" onClick={handleDelete}>Hapus</Button>
 *   <Button variant="secondary" href={route('admin.help.index')}>Batal</Button>
 *   <Button variant="success" type="submit" loading={processing}>Simpan</Button>
 *   <Button href="/sign-in" pill>Sign In</Button>
 *   <Button variant="primary" radius="sm">Simpan</Button>
 *   <Button href="/course" size="lg" radius="xl">Lihat semua kelas</Button>
 */

const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';

const buttonBase = `inline-flex items-center justify-center gap-2 font-semibold transition-all disabled:pointer-events-none disabled:opacity-45 ${focusRing}`;

export const buttonVariants = {
    primary:
        'border border-primary bg-primary text-white shadow-[0_1px_0_rgba(0,0,0,0.08)] hover:border-primary-pressed hover:bg-primary-pressed',
    secondary:
        'border border-hairline-strong bg-canvas text-ink hover:border-hairline-strong hover:bg-surface-soft hover:text-ink',
    success:
        'border border-success bg-success text-white shadow-[0_1px_0_rgba(0,0,0,0.08)] hover:border-success-pressed hover:bg-success-pressed',
    warning:
        'border border-warning bg-warning text-white shadow-[0_1px_0_rgba(0,0,0,0.08)] hover:border-warning-pressed hover:bg-warning-pressed',
    danger:
        'border border-danger bg-danger text-white shadow-[0_1px_0_rgba(0,0,0,0.08)] hover:border-danger-pressed hover:bg-danger-pressed',
};

export const buttonSizes = {
    md: 'min-h-10 px-4 text-sm',
    lg: 'min-h-11 px-5 py-3 text-sm font-bold',
    sm: 'min-h-9 gap-1.5 px-3.5 text-[13px]',
    xs: 'min-h-8 gap-1 px-2.5 text-xs',
};

/** Rounding: default lg (md untuk xs); prop `pill`/`radius` untuk override */
export const buttonRadius = {
    sm: 'rounded',
    md: 'rounded-md',
    lg: 'rounded-lg',
    xl: 'rounded-xl',
    full: 'rounded-full',
};

export default function Button({
    variant = 'primary',
    size = 'md',
    type = 'button',
    href,
    disabled = false,
    loading = false,
    fullWidth = false,
    pill = false,
    radius,
    className = '',
    children,
    ...props
}: {
    variant?: 'primary' | 'secondary' | 'warning' | 'danger' | 'success' | string;
    size?: 'md' | 'lg' | 'sm' | 'xs' | string;
    type?: 'button' | 'reset' | 'submit' | string;
    href?: string;
    disabled?: boolean;
    loading?: boolean;
    fullWidth?: boolean;
    pill?: boolean;
    radius?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    className?: string;
    children?: ReactNode;
    [key: string]: any;
}) {
    const rounding =
        buttonRadius[pill ? 'full' : radius] ??
        (size === 'xs' ? buttonRadius.md : buttonRadius.lg);

    const classes = [
        buttonBase,
        buttonVariants[variant] ?? buttonVariants.primary,
        buttonSizes[size] ?? buttonSizes.md,
        rounding,
        fullWidth ? 'w-full' : '',
        className,
    ]
        .filter(Boolean)
        .join(' ');

    if (href) {
        return (
            <Link href={href} className={classes} {...props}>
                {children}
            </Link>
        );
    }

    return (
        <button
            type={type as any}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={classes}
            {...props}
        >
            {loading && (
                <i className="bx bx-loader-alt bx-spin text-base leading-none" aria-hidden />
            )}
            {children}
        </button>
    );
}
