import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, usePage } from '@inertiajs/react';
import '../../../css/admin-dashboard.css';

interface PopularCourse {
    name: string;
    participants: number;
    completion: number;
}

interface Performance {
    course_id?: string;
    course_title?: string;
    title?: string;
    name?: string;
    avg_pre_test?: number | string | null;
    pre_avg?: number | string | null;
    avg_post_test?: number | string | null;
    post_avg?: number | string | null;
    avg_margin?: number | string | null;
    margin?: number | string | null;
}

const quickActions = [
    { label: 'Kelola kelas', description: 'Materi dan jadwal pelatihan', route: 'admin.course.index', icon: 'bx-book-open' },
    { label: 'Data pengguna', description: 'Pengguna pengembangan kompetensi', route: 'admin.participant.index', icon: 'bx-group' },
    { label: 'Hasil belajar', description: 'Rekap hasil dan penyelesaian', route: 'admin.result.index', icon: 'bx-bar-chart-alt-2' },
    { label: 'Performa kelas', description: 'Evaluasi pre-test dan post-test', route: 'admin.course-performance.index', icon: 'bx-line-chart' },
];

function formatNumber(value: number | string) {
    return Number(value || 0).toLocaleString('id-ID');
}

function numeric(value: unknown): number | null {
    if (value === null || value === undefined || value === '') return null;
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

function score(value: unknown) {
    const number = numeric(value);
    return number === null ? '—' : number.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

function Margin({ value }: { value: unknown }) {
    const margin = numeric(value);
    if (margin === null) return <span className="dashboard-no-value">—</span>;
    return (
        <span className={`dashboard-delta ${margin > 0 ? 'is-positive' : margin < 0 ? 'is-negative' : 'is-neutral'}`}>
            {margin !== 0 && <i className={`bx ${margin > 0 ? 'bx-trending-up' : 'bx-trending-down'}`} aria-hidden="true" />}
            {margin > 0 ? '+' : ''}{score(margin)}
        </span>
    );
}

export default function Dashboard({
    totalCourses = 0,
    totalParticipants = 0,
    totalFinished = 0,
    popularCourses = [],
    result = [],
}: {
    totalCourses?: number;
    totalParticipants?: number;
    totalFinished?: number;
    popularCourses?: PopularCourse[];
    result?: Performance[];
}) {
    const user = usePage().props.auth?.user;
    const firstName = (user?.name || 'Admin').trim().split(' ')[0];
    const courses = (Array.isArray(popularCourses) ? popularCourses : []).slice(0, 6);
    const evaluations = Array.isArray(result) ? result : [];
    const performance = evaluations.slice(0, 6);
    const date = new Date().toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta',
    });
    const metrics = [
        { label: 'Total kelas', value: totalCourses, note: 'Kelas pelatihan terdaftar', icon: 'bx-book-open', route: 'admin.course.index' },
        { label: 'Total pengguna', value: totalParticipants, note: 'Akun pengguna terdaftar', icon: 'bx-group', route: 'admin.participant.index' },
        { label: 'Pelatihan selesai', value: totalFinished, note: 'Total penyelesaian kelas', icon: 'bx-check-shield', route: 'admin.result.index', accent: true },
        { label: 'Kelas dievaluasi', value: evaluations.length, note: 'Memiliki hasil evaluasi', icon: 'bx-line-chart', route: 'admin.course-performance.index', featured: true },
    ];

    return (
        <AdminLayout title="Dashboard" hideTitle>
            <Head title="Dashboard" />
            <div className="admin-dashboard">
                <div className="dashboard-heading">
                    <div>
                        <p className="dashboard-eyebrow">PUSAT ADMINISTRASI</p>
                        <h1>Dashboard</h1>
                        <p className="dashboard-intro">Ringkasan pengembangan kompetensi dalam satu tampilan.</p>
                    </div>
                    <span className="dashboard-date"><i className="bx bx-calendar" aria-hidden="true" />{date}</span>
                </div>

                <section className="dashboard-welcome" aria-labelledby="welcome-title">
                    <div className="dashboard-welcome-copy">
                        <span className="dashboard-welcome-label"><span aria-hidden="true" />GAPURA · SMART ASN</span>
                        <h2 id="welcome-title">Selamat datang kembali, {firstName}.</h2>
                        <p>Dukung ASN terus bertumbuh. Kelola pelatihan dan pantau perkembangan belajar pengguna dari sini.</p>
                    </div>
                    <Link href={route('admin.course.create')} className="dashboard-create">
                        <i className="bx bx-plus" aria-hidden="true" />Buat kelas baru<i className="bx bx-right-arrow-alt" aria-hidden="true" />
                    </Link>
                </section>

                <section aria-labelledby="summary-title" className="dashboard-summary">
                    <div className="dashboard-section-line">
                        <h2 id="summary-title">Ringkasan platform</h2>
                        <span>Akumulasi data pelatihan</span>
                    </div>
                    <div className="dashboard-metrics">
                        {metrics.map((metric) => (
                            <Link href={route(metric.route)} key={metric.label} className={`dashboard-metric ${metric.featured ? 'is-featured' : ''} ${metric.accent ? 'has-orange-accent' : ''}`}>
                                <div className="dashboard-metric-top">
                                    <span className="dashboard-metric-icon"><i className={`bx ${metric.icon}`} aria-hidden="true" /></span>
                                    <i className="bx bx-up-arrow-alt dashboard-metric-arrow" aria-hidden="true" />
                                </div>
                                <p className="dashboard-metric-label">{metric.label}</p>
                                <p className="dashboard-metric-value">{formatNumber(metric.value)}</p>
                                <p className="dashboard-metric-note">{metric.note}</p>
                            </Link>
                        ))}
                    </div>
                </section>

                <section aria-labelledby="quick-actions-title" className="dashboard-shortcuts">
                    <div className="dashboard-section-line"><h2 id="quick-actions-title">Akses cepat</h2></div>
                    <div className="dashboard-actions">
                        {quickActions.map((action) => (
                            <Link href={route(action.route)} key={action.label} className="dashboard-action">
                                <span className="dashboard-action-icon"><i className={`bx ${action.icon}`} aria-hidden="true" /></span>
                                <div><h3>{action.label}</h3><p>{action.description}</p></div>
                                <i className="bx bx-chevron-right dashboard-action-arrow" aria-hidden="true" />
                            </Link>
                        ))}
                    </div>
                </section>

                <div className="dashboard-details">
                    <section className="dashboard-card" aria-labelledby="popular-title">
                        <header className="dashboard-card-header">
                            <div><h2 id="popular-title">Kelas terpopuler</h2><p>Pelatihan dengan jumlah pengguna terbanyak.</p></div>
                            <Link href={route('admin.course.index')} className="dashboard-text-link">Lihat semua<i className="bx bx-right-arrow-alt" aria-hidden="true" /></Link>
                        </header>
                        {courses.length === 0 ? (
                            <div className="dashboard-empty"><i className="bx bx-book-open" aria-hidden="true" /><h3>Belum ada kelas dengan pengguna</h3><p>Ringkasan akan tampil setelah pengguna bergabung ke kelas.</p></div>
                        ) : (
                            <ol className="dashboard-course-list">
                                {courses.map((course, index) => {
                                    const completion = Math.max(0, Math.min(100, numeric(course.completion) ?? 0));
                                    return (
                                        <li key={`${course.name}-${index}`} className="dashboard-course">
                                            <span className={`dashboard-course-rank ${index === 0 ? 'is-first' : ''}`}>{String(index + 1).padStart(2, '0')}</span>
                                            <div className="dashboard-course-body">
                                                <div className="dashboard-course-top"><h3>{course.name}</h3><span className="dashboard-course-count"><i className="bx bx-user" aria-hidden="true" />{formatNumber(course.participants)}<span>pengguna</span></span></div>
                                                <div className="dashboard-course-progress">
                                                    <div className="dashboard-progress-track" role="progressbar" aria-label={`Ketuntasan ${course.name}`} aria-valuenow={completion} aria-valuemin={0} aria-valuemax={100}>
                                                        <span style={{ width: `${completion}%` }} />
                                                    </div>
                                                    <span>{score(completion)}% selesai</span>
                                                </div>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ol>
                        )}
                        <footer className="dashboard-card-footer"><i className="bx bx-info-circle" aria-hidden="true" />Ketuntasan dihitung dari pengguna yang menyelesaikan kelas.</footer>
                    </section>

                    <section className="dashboard-card" aria-labelledby="performance-title">
                        <header className="dashboard-card-header">
                            <div><h2 id="performance-title">Evaluasi pembelajaran</h2><p>Perbandingan rata-rata nilai per kelas.</p></div>
                            <Link href={route('admin.course-performance.index')} className="dashboard-text-link">Detail<i className="bx bx-right-arrow-alt" aria-hidden="true" /></Link>
                        </header>
                        {performance.length === 0 ? (
                            <div className="dashboard-empty"><i className="bx bx-bar-chart-alt-2" aria-hidden="true" /><h3>Hasil evaluasi belum tersedia</h3><p>Nilai pre-test dan post-test akan muncul setelah evaluasi dikerjakan.</p></div>
                        ) : (
                            <div className="dashboard-performance-scroll" tabIndex={0} role="region" aria-label="Tabel evaluasi pembelajaran">
                                <table className="dashboard-performance-table" data-unstyled>
                                    <caption className="sr-only">Nilai rata-rata pre-test, post-test, dan selisih per kelas</caption>
                                    <thead><tr><th scope="col">Kelas</th><th scope="col">Pre-test</th><th scope="col">Post-test</th><th scope="col">Selisih</th></tr></thead>
                                    <tbody>
                                        {performance.map((row, index) => (
                                            <tr key={row.course_id ?? index}>
                                                <th scope="row">{row.course_title ?? row.title ?? row.name ?? '—'}</th>
                                                <td>{score(row.avg_pre_test ?? row.pre_avg)}</td>
                                                <td><span className="dashboard-post-score">{score(row.avg_post_test ?? row.post_avg)}</span></td>
                                                <td><Margin value={row.avg_margin ?? row.margin} /></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <footer className="dashboard-card-footer"><span className="dashboard-legend-dot" aria-hidden="true" />Selisih positif menunjukkan peningkatan nilai.</footer>
                    </section>
                </div>
            </div>
        </AdminLayout>
    );
}
