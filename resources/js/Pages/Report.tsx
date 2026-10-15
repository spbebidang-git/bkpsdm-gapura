import DataTable from '@/Components/Admin/DataTable';
import { btnPrimarySm } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import '@/../css/admin-crud.css';
import '@/../css/admin-report.css';

const typeLabels: Record<string, string> = {
    'pre-test': 'Pre-test',
    'post-test': 'Post-test',
    task: 'Tugas',
    video: 'Video presentasi',
    meeting: 'Meeting / Zoom',
    literature: 'Literatur',
};

const statusStyles: Record<string, string> = {
    not_started: 'border border-hairline bg-surface-soft text-charcoal',
    available: 'border border-[#F0CAA1] bg-[#FFF4E8] text-[#8A4300]',
    in_progress: 'border border-[#B8D9E2] bg-[#EAF5F6] text-[#17606A]',
    done: 'border border-[#B9DDC5] bg-[#EAF7EE] text-[#246A3C]',
};

const dateFormatter = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

function formatDate(value: unknown) {
    if (!value) return '—';
    const date = new Date(String(value));
    return Number.isNaN(date.getTime()) ? String(value) : dateFormatter.format(date);
}

function formatNumber(value: number | null | undefined) {
    return value === null || value === undefined ? '—' : value.toLocaleString('id-ID');
}

function FilterField({ label, children }: { label: string; children: React.ReactNode }) {
    return <label className="block space-y-1.5"><span className="text-[13px] font-medium text-primary-pressed">{label}</span>{children}</label>;
}

function KpiCard({ label, value, note, icon, tone }: { label: string; value: string; note: string; icon: string; tone: string }) {
    return <div className="admin-panel relative overflow-hidden p-4 sm:p-5"><span className="absolute inset-x-0 top-0 h-[3px] bg-primary" /><div className="flex items-start justify-between gap-3"><div><p className="text-xs font-medium text-steel">{label}</p><p className="mt-2 text-[26px] font-semibold leading-none tracking-[-0.03em] tabular-nums text-ink">{value}</p></div><span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${tone}`}><i className={`bx ${icon} text-lg`} aria-hidden /></span></div><p className="mt-3 text-xs text-stone">{note}</p></div>;
}

type ReportFilters = Record<string, string>;
type ReportSummary = { total_participants?: number; not_started?: number; in_progress?: number; completed?: number; avg_pre_test?: number | null; avg_post_test?: number | null };

export default function Report({ filters: initialFilters = {}, summary = {}, courses = [], participants = [], activityTypes = [], statusOptions = [] }: { filters?: ReportFilters; summary?: ReportSummary; courses?: Array<{ id: string | number; title: string }>; participants?: Array<{ id: string | number; name: string }>; activityTypes?: string[]; statusOptions?: Array<{ value: string; label: string }> }) {
    const [filters, setFilters] = useState({ course_id: initialFilters.course_id || '', participant_id: initialFilters.participant_id || '', activity_type: initialFilters.activity_type || '', status: initialFilters.status || '', enrolled_from: initialFilters.enrolled_from || '', enrolled_to: initialFilters.enrolled_to || '', activity_from: initialFilters.activity_from || '', activity_to: initialFilters.activity_to || '' });
    const [filterOpen, setFilterOpen] = useState(true);
    const [selected, setSelected] = useState<any>(null);
    const [viewMode, setViewMode] = useState<'activity' | 'course' | 'participant'>('activity');

    useEffect(() => {
        setFilters((current) => ({ ...current, ...initialFilters }));
    }, [JSON.stringify(initialFilters)]);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => event.key === 'Escape' && setSelected(null);
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const applyFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(route('admin.report'), filters, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearFilters = () => {
        const cleared = { course_id: '', participant_id: '', activity_type: '', status: '', enrolled_from: '', enrolled_to: '', activity_from: '', activity_to: '' };
        setFilters(cleared);
        router.get(route('admin.report'), cleared, { preserveState: true, preserveScroll: true, replace: true });
    };

    const columns = useMemo(() => [
        { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => { const { pageIndex, pageSize } = table.getState().pagination; return <span className="tabular-nums text-[#8496B0]">{pageIndex * pageSize + row.index + 1}</span>; } },
        { accessorKey: 'participant_name', header: 'Peserta', cell: ({ row, getValue }) => <div className="min-w-[170px]"><p className="font-semibold text-ink">{getValue() || '—'}</p><p className="mt-0.5 text-xs text-steel">{row.original.nip || 'NIP tidak tersedia'}</p></div> },
        { accessorKey: 'course_title', header: 'Kelas', cell: ({ row, getValue }) => <div className="min-w-[170px]"><p className="font-medium text-ink">{getValue() || '—'}</p><p className="mt-0.5 text-xs text-steel">{row.original.schedule_title || 'Tanpa jadwal'}</p></div> },
        { accessorKey: 'activity_title', header: 'Kegiatan', cell: ({ row, getValue }) => <div className="min-w-[190px]"><p className="font-medium text-ink">{getValue() || '—'}</p><p className="mt-0.5 text-xs text-steel">{typeLabels[row.original.activity_type] || row.original.activity_type}</p></div> },
        { accessorKey: 'status_label', header: 'Status', enableGlobalFilter: false, enableSorting: false, cell: ({ row, getValue }) => <span className={`inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyles[row.original.status] || statusStyles.available}`}>{getValue() || '—'}</span> },
        { accessorKey: 'score', header: 'Nilai', enableGlobalFilter: false, enableSorting: false, meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums font-semibold text-ink">{getValue() ?? '—'}</span> },
        { accessorKey: 'updated_at', header: 'Aktivitas terakhir' },
        { id: 'aksi', header: '', enableSorting: false, enableGlobalFilter: false, cell: ({ row }) => <button type="button" onClick={() => setSelected(row.original)} className="rounded-lg border border-hairline px-3 py-1.5 text-xs font-semibold text-primary transition hover:border-primary/40 hover:bg-primary-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">Detail</button> },
    ], []);

    const groupColumns = useMemo(() => {
        const isCourse = viewMode === 'course';
        return [
            { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => { const { pageIndex, pageSize } = table.getState().pagination; return <span className="tabular-nums text-[#8496B0]">{pageIndex * pageSize + row.index + 1}</span>; } },
            { accessorKey: 'label', header: isCourse ? 'Kelas' : 'Peserta', cell: ({ row, getValue }) => <div className="min-w-[200px]"><p className="font-semibold text-ink">{getValue() || '—'}</p><p className="mt-0.5 text-xs text-steel">{row.original.secondary || '—'}</p></div> },
            { accessorKey: isCourse ? 'participant_count' : 'course_count', header: isCourse ? 'Peserta' : 'Kelas', meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums text-ink">{getValue() ?? 0}</span> },
            { accessorKey: 'activity_count', header: 'Aktivitas', meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums text-ink">{getValue() ?? 0}</span> },
            { accessorKey: 'completion', header: 'Penyelesaian', meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums font-semibold text-primary">{getValue() ?? 0}%</span> },
            { accessorKey: 'avg_pre_test', header: 'Rata-rata pre', meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums text-ink">{getValue() ?? '—'}</span> },
            { accessorKey: 'avg_post_test', header: 'Rata-rata post', meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums text-ink">{getValue() ?? '—'}</span> },
            { accessorKey: 'updated_at', header: 'Aktivitas terakhir' },
            { id: 'aksi', header: '', enableSorting: false, enableGlobalFilter: false, cell: ({ row }) => <button type="button" onClick={() => { const next = { ...filters, ...(isCourse ? { course_id: row.original.course_id } : { participant_id: row.original.participant_id }) }; setFilters(next); setViewMode('activity'); router.get(route('admin.report'), next, { preserveState: true, preserveScroll: true, replace: true }); }} className="rounded-lg border border-hairline px-3 py-1.5 text-xs font-semibold text-primary transition hover:border-primary/40 hover:bg-primary-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">Lihat detail</button> },
        ];
    }, [viewMode, filters]);

    return <AdminLayout title="Laporan pembelajaran" hideTitle><Head title="Laporan Pembelajaran" /><div className="space-y-5">
        <section className="relative overflow-hidden rounded-xl bg-[#164F50] px-5 py-6 text-white sm:px-7 sm:py-7">
            <div className="absolute inset-y-0 right-0 hidden w-1/3 border-l border-white/10 lg:block" aria-hidden>
                <div className="absolute left-8 top-0 h-full w-px bg-white/10" />
                <div className="absolute left-16 top-0 h-full w-px bg-white/10" />
            </div>
            <div className="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-medium tracking-wide text-[#CFE2DD]">Insight pembelajaran</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-[-0.03em] sm:text-[30px]">Laporan terpadu</h1>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-[#DCEAE6]">Pantau progres peserta dan seluruh kegiatan pembelajaran dari satu tempat.</p>
                </div>
                <span className="inline-flex min-h-10 shrink-0 items-center gap-2 self-start rounded-lg border border-white/25 bg-white/10 px-4 text-sm font-semibold text-white sm:self-auto">
                    <i className="bx bx-refresh text-base text-[#9AE5D9]" aria-hidden />Data langsung
                </span>
            </div>
        </section>

        <section className="admin-panel p-4 sm:p-5"><div className="flex items-center justify-between gap-3"><div><p className="text-sm font-semibold text-ink">Filter laporan</p><p className="mt-0.5 text-xs text-steel">Gunakan filter untuk mempersempit data yang ditampilkan.</p></div><button type="button" onClick={() => setFilterOpen((open) => !open)} className="rounded-lg px-2.5 py-2 text-xs font-semibold text-primary hover:bg-primary-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">{filterOpen ? 'Sembunyikan' : 'Tampilkan'} <i className={`bx ${filterOpen ? 'bx-chevron-up' : 'bx-chevron-down'} align-middle text-base`} aria-hidden /></button></div>{filterOpen && <form onSubmit={applyFilters} className="mt-4 space-y-4 border-t border-hairline pt-4"><div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <FilterField label="Kelas"><select value={filters.course_id} onChange={(event) => setFilters({ ...filters, course_id: event.target.value })} className="form-control w-full"><option value="">Semua kelas</option>{courses.map((course) => <option key={course.id} value={course.id}>{course.title}</option>)}</select></FilterField>
            <FilterField label="Peserta"><select value={filters.participant_id} onChange={(event) => setFilters({ ...filters, participant_id: event.target.value })} className="form-control w-full"><option value="">Semua peserta</option>{participants.map((participant) => <option key={participant.id} value={participant.id}>{participant.name}</option>)}</select></FilterField>
            <FilterField label="Jenis kegiatan"><select value={filters.activity_type} onChange={(event) => setFilters({ ...filters, activity_type: event.target.value })} className="form-control w-full"><option value="">Semua jenis</option>{activityTypes.map((type) => <option key={type} value={type}>{typeLabels[type] || type}</option>)}</select></FilterField>
            <FilterField label="Status kegiatan"><select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })} className="form-control w-full"><option value="">Semua status</option>{statusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}</select></FilterField>
        </div><div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><FilterField label="Terdaftar dari"><input type="date" value={filters.enrolled_from} onChange={(event) => setFilters({ ...filters, enrolled_from: event.target.value })} className="form-control w-full" /></FilterField><FilterField label="Terdaftar sampai"><input type="date" value={filters.enrolled_to} onChange={(event) => setFilters({ ...filters, enrolled_to: event.target.value })} className="form-control w-full" /></FilterField><FilterField label="Aktivitas dari"><input type="date" value={filters.activity_from} onChange={(event) => setFilters({ ...filters, activity_from: event.target.value })} className="form-control w-full" /></FilterField><FilterField label="Aktivitas sampai"><input type="date" value={filters.activity_to} onChange={(event) => setFilters({ ...filters, activity_to: event.target.value })} className="form-control w-full" /></FilterField></div><div className="flex flex-wrap items-center justify-end gap-2"><button type="button" onClick={clearFilters} className="rounded-lg px-3 py-2 text-sm font-semibold text-steel hover:bg-surface">Reset</button><button type="submit" className={btnPrimarySm}><i className="bx bx-filter-alt mr-1" aria-hidden />Terapkan filter</button></div></form>}</section>

        <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6"><KpiCard label="Peserta" value={formatNumber(summary.total_participants)} note="enrollment terpilih" icon="bx-group" tone="bg-primary-mist text-primary" /><KpiCard label="Belum mulai" value={formatNumber(summary.not_started)} note="belum ada aktivitas" icon="bx-time-five" tone="bg-surface text-steel" /><KpiCard label="Berjalan" value={formatNumber(summary.in_progress)} note="sedang mengerjakan" icon="bx-loader-circle" tone="bg-[#EAF5F6] text-[#17606A]" /><KpiCard label="Selesai" value={formatNumber(summary.completed)} note="kelas dituntaskan" icon="bx-check-circle" tone="bg-[#EAF7EE] text-[#246A3C]" /><KpiCard label="Rata-rata pre" value={formatNumber(summary.avg_pre_test)} note="nilai tersimpan" icon="bx-bar-chart-alt-2" tone="bg-[#FFF4E8] text-[#8A4300]" /><KpiCard label="Rata-rata post" value={formatNumber(summary.avg_post_test)} note="nilai tersimpan" icon="bx-line-chart" tone="bg-primary-mist text-primary" /></section>

        <div className="flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm font-semibold text-ink">Tampilan laporan</p><p className="mt-0.5 text-xs text-steel">Pilih ringkasan atau buka detail kegiatan.</p></div><div className="inline-flex rounded-xl border border-hairline bg-white p-1" role="tablist" aria-label="Kelompok laporan">{([['activity', 'Detail kegiatan'], ['course', 'Per kelas'], ['participant', 'Per peserta']] as const).map(([mode, label]) => <button key={mode} type="button" role="tab" aria-selected={viewMode === mode} onClick={() => setViewMode(mode)} className={`rounded-lg px-3 py-2 text-xs font-semibold transition ${viewMode === mode ? 'bg-primary text-white shadow-sm' : 'text-steel hover:bg-surface hover:text-ink'}`}>{label}</button>)}</div></div>
        {viewMode === 'activity' ? <DataTable key={`activity-${JSON.stringify(filters)}`} url={route('admin.report.data')} columns={columns} params={filters} pageSize={15} className="admin-crud-table report-table" searchPlaceholder="Cari peserta, kelas, atau kegiatan…" emptyTitle="Belum ada data laporan" emptyDescription="Coba ubah filter atau pastikan peserta sudah terdaftar pada kelas." /> : <DataTable key={`${viewMode}-${JSON.stringify(filters)}`} url={route('admin.report.groups')} columns={groupColumns} params={{ ...filters, group_by: viewMode }} pageSize={15} className="admin-crud-table report-table" searchPlaceholder={viewMode === 'course' ? 'Cari nama kelas…' : 'Cari nama peserta…'} emptyTitle="Belum ada ringkasan" emptyDescription="Coba ubah filter untuk melihat ringkasan." />}
    </div>

    {selected && <div className="fixed inset-0 z-50 flex items-end justify-center bg-[rgba(0,20,30,0.5)] p-0 backdrop-blur-[4px] sm:items-center sm:p-5" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setSelected(null)}><aside className="max-h-[92vh] w-full overflow-y-auto rounded-t-[18px] bg-white p-5 shadow-[0_16px_48px_rgba(0,20,30,0.2)] sm:max-w-xl sm:rounded-[18px] sm:p-7" role="dialog" aria-modal="true" aria-labelledby="report-detail-title"><div className="flex items-start justify-between gap-4 border-b border-hairline pb-4"><div><p className="text-xs font-medium uppercase tracking-wider text-primary">Detail aktivitas</p><h2 id="report-detail-title" className="mt-1 text-xl font-semibold text-ink">{selected.activity_title}</h2></div><button type="button" onClick={() => setSelected(null)} className="flex h-9 w-9 items-center justify-center rounded-lg text-steel hover:bg-surface hover:text-ink" aria-label="Tutup detail"><i className="bx bx-x text-xl" aria-hidden /></button></div><div className="mt-5 space-y-5"><div className="rounded-2xl bg-surface p-4"><p className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Peserta</p><p className="mt-1 font-semibold text-ink">{selected.participant_name}</p><p className="mt-0.5 text-sm text-steel">{selected.nip} · {selected.jabatan}</p></div><dl className="grid gap-x-5 gap-y-4 sm:grid-cols-2"><div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Kelas</dt><dd className="mt-1 text-sm font-medium text-ink">{selected.course_title}</dd></div><div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Jenis</dt><dd className="mt-1 text-sm text-ink">{typeLabels[selected.activity_type] || selected.activity_type}</dd></div><div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Status</dt><dd className="mt-1"><span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyles[selected.status] || statusStyles.available}`}>{selected.status_label}</span></dd></div><div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Terakhir diperbarui</dt><dd className="mt-1 text-sm text-ink">{formatDate(selected.updated_at)}</dd></div><div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Terdaftar</dt><dd className="mt-1 text-sm text-ink">{formatDate(selected.enrolled_at)}</dd></div><div><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-steel">Durasi</dt><dd className="mt-1 text-sm text-ink">{selected.activity_duration ? `${selected.activity_duration} menit` : '—'}</dd></div></dl>{(selected.activity_type === 'pre-test' || selected.activity_type === 'post-test') && <div className="rounded-2xl border border-hairline p-4"><p className="text-sm font-semibold text-ink">Ringkasan nilai</p><div className="mt-3 grid grid-cols-3 gap-3 text-center"><div><p className="text-xl font-semibold text-ink">{selected.score ?? '—'}</p><p className="text-xs text-steel">Nilai</p></div><div><p className="text-xl font-semibold text-[#21734C]">{selected.correct ?? '—'}</p><p className="text-xs text-steel">Benar</p></div><div><p className="text-xl font-semibold text-[#A43B2F]">{selected.wrong ?? '—'}</p><p className="text-xs text-steel">Salah</p></div></div></div>}{selected.attachment_url && <a href={selected.attachment_url} target="_blank" rel="noreferrer" className="flex items-center gap-3 rounded-2xl border border-hairline p-4 text-sm font-semibold text-primary hover:border-primary/40 hover:bg-primary-mist"><i className="bx bx-paperclip text-xl" aria-hidden />Unduh berkas tugas<i className="bx bx-link-external ml-auto" aria-hidden /></a>}</div></aside></div>}
    </AdminLayout>;
}
