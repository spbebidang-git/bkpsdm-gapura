import {
    badgeOk,
    badgePending,
    badgeWarn,
    btnLink,
    card,
    pageEyebrow,
    sectionDescription,
    sectionTitle,
    tableClass,
    tableScroller,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, usePage } from '@inertiajs/react';

const quickActions = [
    { label: 'Buat kelas', route: 'admin.course.create', icon: 'bx-plus' },
    { label: 'Lihat peserta', route: 'admin.participant.index', icon: 'bx-group' },
    { label: 'Periksa hasil', route: 'admin.result.index', icon: 'bx-bar-chart-alt-2' },
    { label: 'Sinkron sertifikat', route: 'admin.certificate.index', icon: 'bx-certification' },
    { label: 'Import data', route: 'admin.import-learn.index', icon: 'bx-import' },
    { label: 'Buka performa', route: 'admin.course-performance.index', icon: 'bx-line-chart' },
];

function formatNumber(value) {
    return Number(value || 0).toLocaleString('id-ID');
}

function today() {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function Completion({ value }) {
    const percentage = Math.max(0, Math.min(100, Number(value) || 0));
    return (
        <div className="flex min-w-[150px] items-center gap-2.5">
            <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-[#DBDDD8]">
                <div className="h-full rounded-full bg-primary" style={{ width: `${percentage}%` }} />
            </div>
            <span className="w-10 text-right text-xs font-semibold tabular-nums text-charcoal">
                {percentage}%
            </span>
        </div>
    );
}

function Margin({ value }) {
    const margin = Number(value);
    if (Number.isNaN(margin)) return <span className="text-stone">—</span>;
    if (margin > 0) return <span className={badgeOk}>+{margin}</span>;
    if (margin < 0) return <span className={badgeWarn}>{margin}</span>;
    return <span className={badgePending}>0</span>;
}

export default function Dashboard({
    totalCourses = 0,
    totalParticipants = 0,
    totalFinished = 0,
    popularCourses = [],
    result = [],
}) {
    const user = usePage().props.auth?.user;
    const completionRate = totalParticipants > 0
        ? Math.round((Number(totalFinished) / Number(totalParticipants)) * 100)
        : 0;
    const firstName = (user?.name || 'Admin').trim().split(' ')[0];
    const topCourses = popularCourses.slice(0, 8);
    const performance = (Array.isArray(result) ? result : []).slice(0, 8);

    const metrics = [
        { label: 'Kelas', value: formatNumber(totalCourses), note: 'aktif dan draft', icon: 'bx-book-open', route: 'admin.course.index' },
        { label: 'Peserta', value: formatNumber(totalParticipants), note: 'akun peserta', icon: 'bx-group', route: 'admin.participant.index' },
        { label: 'Pelatihan selesai', value: formatNumber(totalFinished), note: 'riwayat selesai', icon: 'bx-check-circle', route: 'admin.result.index' },
        { label: 'Rasio selesai', value: `${completionRate}%`, note: 'selesai per peserta', icon: 'bx-pie-chart-alt-2', route: 'admin.course-performance.index' },
    ];

    return (
        <AdminLayout title="Dashboard" hideTitle>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-xl bg-[#164F50] px-5 py-6 text-white sm:px-7 sm:py-7">
                    <div className="absolute inset-y-0 right-0 hidden w-1/3 border-l border-white/10 lg:block" aria-hidden>
                        <div className="absolute left-8 top-0 h-full w-px bg-white/10" />
                        <div className="absolute left-16 top-0 h-full w-px bg-white/10" />
                    </div>
                    <div className="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-medium capitalize tracking-wide text-[#CFE2DD]">{today()}</p>
                            <h1 className="mt-2 text-2xl font-semibold tracking-[-0.03em] sm:text-[30px]">
                                Selamat datang, {firstName}
                            </h1>
                            <p className="mt-2 max-w-xl text-sm leading-6 text-[#DCEAE6]">
                                Pantau kelas, peserta, hasil belajar, dan sertifikat dari satu tempat.
                            </p>
                        </div>
                        <Link
                            href={route('admin.course.create')}
                            className="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-white/25 bg-white px-4 text-sm font-semibold text-primary-pressed transition-colors hover:bg-[#EDF6F3] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:self-auto"
                        >
                            <i className="bx bx-plus text-lg" aria-hidden />
                            Kelas baru
                        </Link>
                    </div>
                </section>

                <section aria-labelledby="dashboard-metrics">
                    <div className="mb-3 flex items-end justify-between gap-3">
                        <div>
                            <p className={pageEyebrow}>Hari ini</p>
                            <h2 id="dashboard-metrics" className="mt-1 text-lg font-semibold text-ink">Ringkasan data</h2>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {metrics.map((metric) => (
                            <Link
                                key={metric.label}
                                href={route(metric.route)}
                                className={`${card} group relative overflow-hidden p-4 transition-colors hover:border-primary/35 hover:bg-[#FAFCF9]`}
                            >
                                <span className="absolute inset-x-0 top-0 h-[3px] bg-primary" />
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-medium text-steel">{metric.label}</p>
                                        <p className="mt-2 text-[28px] font-semibold leading-none tracking-[-0.035em] tabular-nums text-ink">{metric.value}</p>
                                        <p className="mt-2 text-xs text-stone">{metric.note}</p>
                                    </div>
                                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-mist text-primary">
                                        <i className={`bx ${metric.icon} text-lg`} aria-hidden />
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="quick-actions">
                    <div className="border-b border-hairline px-5 py-4">
                        <h2 id="quick-actions" className={sectionTitle}>Aksi cepat</h2>
                        <p className={sectionDescription}>Jalur singkat ke pekerjaan yang paling sering dipakai.</p>
                    </div>
                    <div className="grid sm:grid-cols-2 xl:grid-cols-3">
                        {quickActions.map((action, index) => (
                            <Link
                                key={action.label}
                                href={route(action.route)}
                                className={`group flex min-h-14 items-center gap-3 px-5 py-3 text-sm font-semibold text-charcoal transition-colors hover:bg-primary-mist/45 hover:text-primary-pressed ${index < quickActions.length - 1 ? 'border-b border-hairline-soft xl:border-b-0' : ''} ${index % 3 !== 2 ? 'xl:border-r xl:border-hairline-soft' : ''}`}
                            >
                                <i className={`bx ${action.icon} text-lg text-primary`} aria-hidden />
                                <span>{action.label}</span>
                                <i className="bx bx-right-arrow-alt ml-auto text-lg text-stone transition-transform group-hover:translate-x-0.5 group-hover:text-primary" aria-hidden />
                            </Link>
                        ))}
                    </div>
                </section>

                <div className="grid gap-6 xl:grid-cols-2">
                    <section className={`${card} overflow-hidden`} aria-labelledby="popular-title">
                        <header className="flex items-start justify-between gap-4 border-b border-hairline px-5 py-4">
                            <div>
                                <h2 id="popular-title" className={sectionTitle}>Kelas terpopuler</h2>
                                <p className={sectionDescription}>Diurutkan dari jumlah peserta.</p>
                            </div>
                            <Link href={route('admin.course.index')} className={btnLink}>Lihat kelas</Link>
                        </header>
                        <div className={tableScroller}>
                            <table className={`${tableClass} min-w-[620px]`}>
                                <thead>
                                    <tr>
                                        <th className="w-10 text-left">No.</th>
                                        <th className="text-left">Kelas</th>
                                        <th className="text-right">Peserta</th>
                                        <th className="text-left">Selesai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topCourses.length === 0 && (
                                        <tr><td colSpan={4} className="py-12 text-center text-steel">Belum ada data peserta.</td></tr>
                                    )}
                                    {topCourses.map((course, index) => (
                                        <tr key={`${course.name}-${index}`}>
                                            <td className="tabular-nums text-stone">{index + 1}</td>
                                            <td className="font-semibold text-ink"><span className="line-clamp-1">{course.name}</span></td>
                                            <td className="text-right tabular-nums">{formatNumber(course.participants)}</td>
                                            <td><Completion value={course.completion} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className={`${card} overflow-hidden`} aria-labelledby="performance-title">
                        <header className="flex items-start justify-between gap-4 border-b border-hairline px-5 py-4">
                            <div>
                                <h2 id="performance-title" className={sectionTitle}>Performa pre-test dan post-test</h2>
                                <p className={sectionDescription}>Perubahan rata-rata nilai per kelas.</p>
                            </div>
                            <Link href={route('admin.course-performance.index')} className={btnLink}>Lihat performa</Link>
                        </header>
                        <div className={tableScroller}>
                            <table className={`${tableClass} min-w-[560px]`}>
                                <thead>
                                    <tr>
                                        <th className="text-left">Kelas</th>
                                        <th className="text-right">Pre-test</th>
                                        <th className="text-right">Post-test</th>
                                        <th className="text-right">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {performance.length === 0 && (
                                        <tr><td colSpan={4} className="py-12 text-center text-steel">Belum ada hasil pre-test atau post-test.</td></tr>
                                    )}
                                    {performance.map((row, index) => (
                                        <tr key={`${row.course_id ?? index}`}>
                                            <td className="font-semibold text-ink"><span className="line-clamp-1">{row.course_title ?? row.title ?? row.name ?? '—'}</span></td>
                                            <td className="text-right tabular-nums">{row.avg_pre_test ?? row.pre_avg ?? '—'}</td>
                                            <td className="text-right tabular-nums">{row.avg_post_test ?? row.post_avg ?? '—'}</td>
                                            <td className="text-right"><Margin value={row.avg_margin ?? row.margin} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </AdminLayout>
    );
}
