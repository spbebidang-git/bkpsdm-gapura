import AdminAlert from '@/Components/Admin/AdminAlert';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const navigation = [
    {
        label: 'Ringkasan',
        hideLabel: true,
        items: [
            { label: 'Dashboard', route: 'admin.dashboard', match: 'admin.dashboard', icon: 'bx-grid-alt' },
        ],
    },
    {
        label: 'Pembelajaran',
        items: [
            { label: 'Kelas', route: 'admin.course.index', match: 'admin.course.*', icon: 'bx-book-open' },
            { label: 'Kategori', route: 'admin.category.index', match: 'admin.category.*', icon: 'bx-category' },
            { label: 'Trainer', route: 'admin.trainer.index', match: 'admin.trainer.*', icon: 'bx-user-voice' },
            { label: 'Peserta', route: 'admin.participant.index', match: 'admin.participant.*', icon: 'bx-group' },
            { label: 'Hasil', route: 'admin.result.index', match: 'admin.result.*', icon: 'bx-bar-chart-alt-2' },
            { label: 'Performa', route: 'admin.course-performance.index', match: 'admin.course-performance.*', icon: 'bx-line-chart' },
        ],
    },
    {
        label: 'Operasional',
        items: [
            { label: 'Sertifikat', route: 'admin.certificate.index', match: 'admin.certificate.*', icon: 'bx-certification' },
            { label: 'Import data', route: 'admin.import-learn.index', match: 'admin.import-learn.*', icon: 'bx-import' },
            { label: 'Laporan', route: 'admin.report', match: 'admin.report', icon: 'bx-file' },
            { label: 'Ulasan', route: 'admin.review.index', match: 'admin.review.*', icon: 'bx-star' },
            { label: 'Bantuan', route: 'admin.help.index', match: 'admin.help.*', icon: 'bx-help-circle' },
        ],
    },
    {
        label: 'Sistem',
        items: [
            { label: 'Pengguna', route: 'admin.management.user.index', match: 'admin.management.user.*', icon: 'bx-user' },
            { label: 'Peran', route: 'admin.management.role.index', match: 'admin.management.role.*', icon: 'bx-shield-alt-2' },
            { label: 'Hak akses', route: 'admin.management.permission.index', match: 'admin.management.permission.*', icon: 'bx-key' },
            { label: 'Pengaturan', route: 'admin.management.setting.index', match: 'admin.management.setting.*', icon: 'bx-cog' },
            { label: 'Log & backup', route: 'admin.management.log.index', match: 'admin.management.log.*', icon: 'bx-receipt' },
        ],
    },
];

const items = navigation.flatMap((section) => section.items);

function isActive(match) {
    try {
        return Boolean(route().current(match));
    } catch {
        return false;
    }
}

function currentItem() {
    try {
        const name = route().current();
        if (!name) return null;
        return items.find((item) => name.startsWith(item.match.replace('.*', ''))) ?? null;
    } catch {
        return null;
    }
}

export default function AdminLayout({ title = 'Admin', breadcrumbs = null, hideTitle = false, actions = null, fullWidth = false, children }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const { auth, flash } = usePage().props;
    const user = auth?.user;
    const roleName = Array.isArray(user?.roles)
        ? user.roles[0]?.name || user.roles[0] || 'Administrator'
        : user?.role || 'Administrator';
    const section = currentItem();

    useEffect(() => {
        const closeOnEscape = (event) => {
            if (event.key === 'Escape') setMenuOpen(false);
        };
        window.addEventListener('keydown', closeOnEscape);
        return () => window.removeEventListener('keydown', closeOnEscape);
    }, []);

    return (
        <div className="admin-shell min-h-screen bg-white text-ink">
            {menuOpen && (
                <button
                    type="button"
                    aria-label="Tutup navigasi"
                    className="fixed inset-0 z-40 bg-[#102F31]/45 backdrop-blur-[2px] lg:hidden"
                    onClick={() => setMenuOpen(false)}
                />
            )}

            <aside
                aria-label="Navigasi admin"
                className={`fixed inset-y-0 left-0 z-50 flex w-[272px] flex-col border-r border-[#D7DCDA] bg-[#EFF2F1] transition-transform duration-200 lg:translate-x-0 ${menuOpen ? 'translate-x-0' : '-translate-x-full'}`}
            >
                <div className="relative flex h-[68px] items-center justify-center border-b border-[#D7DCDA] px-5">
                    <Link
                        href={route('admin.dashboard')}
                        className="flex min-w-0 items-center rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <img
                            src="/assets/media/images/static/site_logo.png"
                            alt="BKPSDM Kabupaten Gresik"
                            className="h-9 w-auto max-w-[168px] object-contain object-left"
                        />
                    </Link>
                    <button
                        type="button"
                        aria-label="Tutup navigasi"
                        className="absolute right-3 flex h-9 w-9 items-center justify-center rounded-lg text-steel hover:bg-surface lg:hidden"
                        onClick={() => setMenuOpen(false)}
                    >
                        <i className="bx bx-x text-xl" aria-hidden />
                    </button>
                </div>

                <nav className="admin-nav flex-1 overflow-y-auto px-3 py-4">
                    {navigation.map((group) => (
                        <section key={group.label} className="mb-5 last:mb-2">
                            {!group.hideLabel && (
                                <h2 className="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-stone">{group.label}</h2>
                            )}
                            <div className="space-y-0.5">
                                {group.items.map((item) => {
                                    const active = isActive(item.match);
                                    return (
                                        <Link
                                            key={item.route}
                                            href={route(item.route)}
                                            onClick={() => setMenuOpen(false)}
                                            aria-current={active ? 'page' : undefined}
                                            className={`group relative flex min-h-10 items-center gap-3 rounded-lg px-3 text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${active ? 'bg-[#DDEDEA] text-[#0B5C5E]' : 'text-charcoal hover:bg-[#EFEEE8] hover:text-ink'}`}
                                        >
                                            {active && <span className="absolute -left-3 h-5 w-[3px] rounded-r-full bg-secondary" />}
                                            <i className={`bx ${item.icon} text-[18px] leading-none ${active ? 'text-primary' : 'text-steel group-hover:text-primary'}`} aria-hidden />
                                            <span className="truncate">{item.label}</span>
                                        </Link>
                                    );
                                })}
                            </div>
                        </section>
                    ))}
                </nav>

                <div className="border-t border-[#D7DCDA] p-3 lg:hidden">
                    <div className="flex items-center gap-3 rounded-lg bg-[#E4E9E7] px-3 py-3">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0B5C5E] text-xs font-semibold text-white">
                            {(user?.name || 'A').charAt(0).toUpperCase()}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-[13px] font-semibold text-ink">{user?.name || 'Admin'}</span>
                            <span className="block truncate text-[11px] text-steel">{roleName}</span>
                        </span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            aria-label="Keluar"
                            title="Keluar"
                            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-steel transition-colors hover:bg-[#FDE8E4] hover:text-[#A43B2F] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <i className="bx bx-log-out text-lg" aria-hidden />
                        </Link>
                    </div>
                </div>
            </aside>

            <div className="lg:pl-[272px]">
                <header className="sticky top-0 z-30 flex h-[68px] items-center gap-3 border-b border-[#DDE1DF] bg-white/95 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                    <button
                        type="button"
                        className="flex h-10 w-10 items-center justify-center rounded-lg border border-hairline bg-canvas text-charcoal lg:hidden"
                        onClick={() => setMenuOpen(true)}
                        aria-label="Buka navigasi"
                    >
                        <i className="bx bx-menu text-xl" aria-hidden />
                    </button>

                    <nav aria-label="Breadcrumb" className="flex min-w-0 flex-1 items-center gap-2 overflow-hidden text-[13px]">
                        <Link href={route('admin.dashboard')} className="font-medium text-steel transition-colors hover:text-primary">Admin</Link>
                        {(breadcrumbs || (section?.label ? [{ label: title || section.label }] : [])).map((crumb, index, all) => (
                            <span key={`${crumb.label}-${index}`} className="flex min-w-0 items-center gap-2">
                                <i className="bx bx-chevron-right shrink-0 text-base text-stone" aria-hidden />
                                {crumb.href && index !== all.length - 1 ? (
                                    <Link href={crumb.href} className="truncate font-medium text-steel transition-colors hover:text-primary">{crumb.label}</Link>
                                ) : (
                                    <span className="truncate font-semibold text-ink">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>

                    {actions && <div className="ml-2 flex items-center gap-2">{actions}</div>}
                    <div className="ml-1 hidden items-center gap-3 border-l border-hairline pl-4 lg:flex">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0B5C5E] text-xs font-semibold text-white">
                            {(user?.name || 'A').charAt(0).toUpperCase()}
                        </span>
                        <span className="min-w-0 max-w-40">
                            <span className="block truncate text-[13px] font-semibold leading-4 text-ink">{user?.name || 'Admin'}</span>
                            <span className="mt-0.5 block truncate text-[11px] leading-4 text-steel">{roleName}</span>
                        </span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            aria-label="Keluar"
                            title="Keluar"
                            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-steel transition-colors hover:bg-[#FDE8E4] hover:text-[#A43B2F] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <i className="bx bx-log-out text-lg" aria-hidden />
                        </Link>
                    </div>
                </header>

                <main className="px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
                    <div className={fullWidth ? 'w-full' : 'mx-auto w-full max-w-[1540px]'}>
                        <AdminAlert initialFlash={flash ?? {}} />
                        {!hideTitle && title && (
                            <div className="mb-6 border-l-2 border-secondary pl-4">
                                <h1 className="text-2xl font-semibold tracking-[-0.025em] text-ink">{title}</h1>
                            </div>
                        )}
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
