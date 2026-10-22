import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import ReportSummary, { reportNumber, type ReportSummaryData } from '@/Components/Admin/ReportSummary';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import '@/../css/admin-crud.css';
import '@/../css/admin-report.css';

type Filters = Record<string, string>;
type Entity = { id: string; title: string; type: 'participant' | 'course' };
type Option = { id: string; title?: string; name?: string };
const typeLabels: Record<string, string> = { 'pre-test': 'Pre-test', 'post-test': 'Post-test', task: 'Tugas', video: 'Video', meeting: 'Pertemuan daring', literature: 'Literatur' };
const statuses = [{ value: 'not_started', label: 'Belum dimulai' }, { value: 'available', label: 'Tersedia' }, { value: 'in_progress', label: 'Berjalan' }, { value: 'done', label: 'Selesai' }];

export default function Show({ entity, filters: initialFilters = {}, summary = {}, courses = [], participants = [], activityTypes = [], backUrl }: {
    entity: Entity; filters?: Filters; summary?: ReportSummaryData; courses?: Option[]; participants?: Option[]; activityTypes?: string[]; backUrl: string;
}) {
    const [filters, setFilters] = useState<Filters>(initialFilters);
    useEffect(() => setFilters(initialFilters), [JSON.stringify(initialFilters)]);
    const isCourse = entity.type === 'course';
    const fixedKey = isCourse ? 'course_id' : 'participant_id';
    const fixedFilters = { [fixedKey]: entity.id };
    const detailUrl = route(isCourse ? 'admin.report.course' : 'admin.report.participant', entity.id);
    const update = (key: string, value: string) => setFilters(current => ({ ...current, [key]: value }));
    const apply = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(detailUrl, { ...filters, ...fixedFilters, return_to: backUrl }, { preserveScroll: true });
    };
    const columns = useMemo(() => [
        { accessorKey: isCourse ? 'participant_name' : 'course_title', header: isCourse ? 'Peserta' : 'Kelas', cell: ({ row, getValue }) => <div className="report-entity-cell"><strong>{getValue()}</strong><p>{isCourse ? row.original.nip : row.original.schedule_title}</p></div> },
        { accessorKey: 'activity_title', header: 'Kegiatan', cell: ({ row, getValue }) => <div className="report-entity-cell"><strong>{getValue()}</strong><p>{typeLabels[row.original.activity_type] || row.original.activity_type}{row.original.activity_duration ? ` · ${row.original.activity_duration} menit` : ''}</p></div> },
        { accessorKey: 'status_label', header: 'Status kegiatan', enableSorting: false, enableGlobalFilter: false, cell: ({ row, getValue }) => <span className={`report-status is-${row.original.status}`}>{getValue()}</span> },
        { accessorKey: 'score', header: 'Hasil', enableSorting: false, enableGlobalFilter: false, cell: ({ row, getValue }) => <div className="report-score"><strong>{reportNumber(getValue())}</strong>{row.original.correct !== null && <small>{row.original.correct} benar · {row.original.wrong} salah</small>}</div> },
        { accessorKey: 'updated_at', header: 'Aktivitas terakhir', enableGlobalFilter: false },
        { id: 'aksi', header: 'Berkas', enableSorting: false, enableGlobalFilter: false, cell: ({ row }) => row.original.attachment_url ? <a className="report-detail-link" href={row.original.attachment_url} target="_blank" rel="noopener noreferrer"><i className="bx bx-download" aria-hidden />Unduh tugas<span className="sr-only"> (buka tab baru)</span></a> : <span className="text-steel">—</span> },
    ], [isCourse]);

    return <AdminLayout title={`Laporan ${entity.title}`} hideTitle><Head title={`Laporan ${entity.title}`} /><div className="report-page">
        <nav className="report-breadcrumb" aria-label="Breadcrumb"><Link href={backUrl}>Laporan</Link><i className="bx bx-chevron-right" aria-hidden /><span>{isCourse ? 'Per kelas' : 'Per peserta'}</span><i className="bx bx-chevron-right" aria-hidden /><span aria-current="page">{entity.title}</span></nav>
        <CrudPageHeader title={entity.title} description={isCourse ? 'Detail progres dan hasil kegiatan seluruh peserta pada kelas ini.' : 'Detail kelas, progres kegiatan, dan hasil belajar peserta ini.'} actions={<Link className="report-back" href={backUrl}><i className="bx bx-arrow-back" aria-hidden />Kembali ke laporan</Link>} />
        <ReportSummary summary={summary} detail course={isCourse} />
        <section className="report-collection" aria-labelledby="report-activity-title">
            <header className="report-collection-header"><div><h2 id="report-activity-title">Rincian kegiatan</h2><p>Lihat status, nilai tes, dan berkas tugas untuk setiap kegiatan pembelajaran.</p></div><span className="report-scope"><i className={`bx ${isCourse ? 'bx-book-open' : 'bx-user'}`} aria-hidden />{isCourse ? 'Laporan kelas' : 'Laporan peserta'}</span></header>
            <form className="report-filters" onSubmit={apply}>
                <label><span>{isCourse ? 'Peserta' : 'Kelas'}</span><select className="form-control" value={filters[isCourse ? 'participant_id' : 'course_id'] || ''} onChange={event => update(isCourse ? 'participant_id' : 'course_id', event.target.value)}><option value="">{isCourse ? 'Semua peserta' : 'Semua kelas'}</option>{(isCourse ? participants : courses).map(option => <option key={option.id} value={option.id}>{isCourse ? option.name : option.title}</option>)}</select></label>
                <label><span>Jenis kegiatan</span><select className="form-control" value={filters.activity_type || ''} onChange={event => update('activity_type', event.target.value)}><option value="">Semua jenis</option>{activityTypes.map(type => <option key={type} value={type}>{typeLabels[type] || type}</option>)}</select></label>
                <label><span>Status kegiatan</span><select className="form-control" value={filters.status || ''} onChange={event => update('status', event.target.value)}><option value="">Semua status</option>{statuses.map(status => <option key={status.value} value={status.value}>{status.label}</option>)}</select></label>
                <div className="report-filter-actions"><Link className="report-reset" href={route(isCourse ? 'admin.report.course' : 'admin.report.participant', { [isCourse ? 'course' : 'participant']: entity.id, return_to: backUrl })}>Reset</Link><button type="submit" className="report-apply"><i className="bx bx-filter-alt" aria-hidden />Terapkan</button></div>
            </form>
            <DataTable key={`${entity.id}-${JSON.stringify(initialFilters)}`} url={route('admin.report.data')} params={{ ...initialFilters, ...fixedFilters }} columns={columns} pageSize={10} className="admin-crud-table report-table report-detail-table" dataLabel="kegiatan" searchPlaceholder={isCourse ? 'Cari peserta atau kegiatan…' : 'Cari kelas atau kegiatan…'} emptyTitle="Belum ada rincian kegiatan" emptyDescription="Ubah filter atau pastikan kelas sudah memiliki kegiatan pembelajaran." />
        </section>
    </div></AdminLayout>;
}
