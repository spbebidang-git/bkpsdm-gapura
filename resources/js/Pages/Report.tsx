import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import ReportSummary, { ReportProgress, reportNumber, type ReportSummaryData } from '@/Components/Admin/ReportSummary';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import '@/../css/admin-crud.css';
import '@/../css/admin-report.css';

type Filters = Record<string, string>;
type View = 'participant' | 'course';
type Option = { id: string; title?: string; name?: string };

export default function Report({ filters: initialFilters = {}, summary = {}, courses = [], participants = [], view = 'participant' }: {
    filters?: Filters; summary?: ReportSummaryData; courses?: Option[]; participants?: Option[]; view?: View;
}) {
    const [filters, setFilters] = useState<Filters>(initialFilters);
    useEffect(() => setFilters(initialFilters), [JSON.stringify(initialFilters)]);
    const isCourse = view === 'course';
    const appliedFilters = Object.fromEntries(Object.entries(initialFilters).filter(([key]) => ['course_id', 'participant_id', 'enrolled_from', 'enrolled_to'].includes(key)));
    const returnTo = route('admin.report', { ...appliedFilters, view });
    const update = (key: string, value: string) => setFilters(current => ({ ...current, [key]: value }));
    const apply = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(route('admin.report'), { ...filters, view }, { preserveScroll: true });
    };
    const columns = useMemo(() => [
        { accessorKey: 'label', header: isCourse ? 'Kelas' : 'Peserta', cell: ({ row, getValue }) => <div className="report-identity"><span className="report-row-icon"><i className={`bx ${isCourse ? 'bx-book-open' : 'bx-user'}`} aria-hidden /></span><div><Link href={route(isCourse ? 'admin.report.course' : 'admin.report.participant', { [isCourse ? 'course' : 'participant']: row.original.group_id, ...appliedFilters, return_to: returnTo })} className="report-name">{getValue()}</Link><p>{row.original.secondary}</p></div></div> },
        { accessorKey: isCourse ? 'participant_count' : 'course_count', header: isCourse ? 'Peserta' : 'Kelas diikuti', meta: { align: 'right' }, cell: ({ getValue }) => reportNumber(getValue()) },
        { accessorKey: 'completed_count', header: isCourse ? 'Peserta selesai' : 'Kelas selesai', meta: { align: 'right' }, cell: ({ getValue }) => <span className="report-completed">{reportNumber(getValue())}</span> },
        { accessorKey: 'completion', header: 'Progres kegiatan', enableGlobalFilter: false, cell: ({ getValue }) => <ReportProgress value={Number(getValue())} /> },
        { accessorKey: 'avg_post_test', header: 'Rata-rata post-test', meta: { align: 'right' }, cell: ({ getValue }) => <strong>{reportNumber(getValue())}</strong> },
        { id: 'aksi', header: 'Detail', enableSorting: false, enableGlobalFilter: false, cell: ({ row }) => <Link className="report-detail-link" href={route(isCourse ? 'admin.report.course' : 'admin.report.participant', { [isCourse ? 'course' : 'participant']: row.original.group_id, ...appliedFilters, return_to: returnTo })} aria-label={`Lihat detail ${row.original.label}`}>Lihat detail<i className="bx bx-right-arrow-alt" aria-hidden /></Link> },
    ], [view, JSON.stringify(appliedFilters), returnTo]);

    return <AdminLayout title="Laporan pembelajaran" hideTitle><Head title="Laporan pembelajaran" /><div className="report-page">
        <CrudPageHeader title="Laporan pembelajaran" description="Pantau keikutsertaan, progres, dan hasil belajar peserta." />
        <ReportSummary summary={summary} />
        <section className="report-collection" aria-labelledby="report-list-title">
            <header className="report-collection-header"><div><h2 id="report-list-title">{isCourse ? 'Ringkasan per kelas' : 'Ringkasan per peserta'}</h2><p>{isCourse ? 'Satu baris untuk setiap kelas. Buka detail untuk melihat hasil peserta.' : 'Satu baris untuk setiap peserta. Buka detail untuk melihat kelas dan kegiatan.'}</p></div>
                <nav className="report-tabs" aria-label="Tampilan laporan">{([['participant', 'Per peserta', 'bx-group'], ['course', 'Per kelas', 'bx-book-open']] as const).map(([mode, label, icon]) => <Link key={mode} href={route('admin.report', { ...appliedFilters, view: mode })} className={view === mode ? 'is-active' : ''} aria-current={view === mode ? 'page' : undefined} preserveScroll><i className={`bx ${icon}`} aria-hidden />{label}</Link>)}</nav>
            </header>
            <form onSubmit={apply} className="report-filters">
                <label><span>Kelas</span><select className="form-control" value={filters.course_id || ''} onChange={event => update('course_id', event.target.value)}><option value="">Semua kelas</option>{courses.map(course => <option key={course.id} value={course.id}>{course.title}</option>)}</select></label>
                <label><span>Peserta</span><select className="form-control" value={filters.participant_id || ''} onChange={event => update('participant_id', event.target.value)}><option value="">Semua peserta</option>{participants.map(participant => <option key={participant.id} value={participant.id}>{participant.name}</option>)}</select></label>
                <label><span>Aktivasi dari</span><input type="date" className="form-control" value={filters.enrolled_from || ''} onChange={event => update('enrolled_from', event.target.value)} /></label>
                <label><span>Aktivasi sampai</span><input type="date" className="form-control" value={filters.enrolled_to || ''} onChange={event => update('enrolled_to', event.target.value)} min={filters.enrolled_from || undefined} /></label>
                <div className="report-filter-actions"><Link href={route('admin.report', { view })} className="report-reset">Reset</Link><button type="submit" className="report-apply"><i className="bx bx-filter-alt" aria-hidden />Terapkan</button></div>
            </form>
            <DataTable key={`${view}-${JSON.stringify(appliedFilters)}`} url={route('admin.report.groups')} columns={columns} params={{ ...appliedFilters, group_by: view }} pageSize={10} showUpdatedAt={false} className="admin-crud-table report-table" dataLabel={isCourse ? 'kelas' : 'peserta'} searchPlaceholder={isCourse ? 'Cari nama kelas…' : 'Cari nama peserta atau NIP…'} emptyTitle={isCourse ? 'Belum ada laporan kelas' : 'Belum ada laporan peserta'} emptyDescription="Ubah filter atau tunggu peserta mengaktifkan kelas." />
        </section>
    </div></AdminLayout>;
}
