import Modal from '@/Components/Modal';
import { DialogTitle } from '@headlessui/react';
import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import { btnSecondarySm } from '@/Components/Admin/ui';
import '@/../css/admin-course-index.css';
import '@/../css/admin-certificate.css';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';

export default function Index({ courses = [] }) {
    const [preview, setPreview] = useState(null);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [courseId, setCourseId] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [tableKey, setTableKey] = useState(0);
    const processing = useRef(false);
    const [syncing, setSyncing] = useState<string | null>(null);

    const filterParams = useMemo(
        () => ({
            q: search,
            status_filter: status,
            course_filter: courseId,
            date_from: dateFrom,
            date_to: dateTo,
        }),
        [status, courseId, dateFrom, dateTo, search]
    );

    const columns = useMemo(
        () => [
            {
                id: 'DT_RowIndex',
                header: 'No',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ row, table }) => {
                    const { pageIndex, pageSize } = table.getState().pagination;
                    return (
                        <span className="tabular-nums text-[#8496B0]">
                            {pageIndex * pageSize + row.index + 1}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'name',
                enableSorting: false,
                enableGlobalFilter: false,
                header: 'Nama',
                cell: ({ getValue }) => (
                    <span className="certificate-name">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'course',
                enableSorting: false,
                enableGlobalFilter: false,
                header: 'Kelas',
                cell: ({ getValue }) => (
                    <span className="certificate-course">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'finished_at',
                enableGlobalFilter: false,
                header: 'Selesai',
                cell: ({ getValue }) => (
                    <span className="tabular-nums text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'status_label',
                header: 'Status',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ row, getValue }) => {
                    const s = row.original?.status_code;
                    const map = {
                        available: 'certificate-status is-available',
                        sent: 'certificate-status is-sent',
                        pending: 'certificate-status is-pending',
                    };
                    return (
                        <span className={map[s] || 'certificate-status is-pending'}>
                            {getValue() || 'Menunggu'}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'url_certificate',
                header: 'Sertifikat',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => {
                    const url = getValue();
                    if (!url || !/^https?:\/\//i.test(url)) {
                        return <span className="text-[#C2C9D6]">—</span>;
                    }
                    return (
                        <button type="button" onClick={() => setPreview(url)} className="certificate-preview">
                            <i className="bx bx-show" aria-hidden /> Pratinjau
                        </button>
                    );
                },
            },
        ],
        []
    );

    const sync = (type) => {
        if (processing.current) return;
        processing.current = true;
        setSyncing(type);
        const url =
            type === 'push'
                ? route('admin.certificate.sync-push')
                : route('admin.certificate.sync-pull');
        router.post(
            url,
            { course_id: courseId || null },
            {
                preserveScroll: true,
                onSuccess: () => setTableKey((k) => k + 1),
                onFinish: () => {
                    processing.current = false;
                    setSyncing(null);
                },
            }
        );
    };

    return (
        <AdminLayout title="Sertifikat" hideTitle>
            <Head title="Sertifikat" />
            <div className="certificate-admin course-index">
                <CrudPageHeader
                    title="Sertifikat"
                    description="Kelola sertifikat peserta yang telah menyelesaikan kelas dan sinkronkan dengan Kedaton SSO."
                    actions={<>
                        <a href={`${route('admin.certificate.export')}?${new URLSearchParams(filterParams)}`} className="course-index-create">
                            <i className="bx bx-download" aria-hidden /> Ekspor Excel
                        </a>
                        <button type="button" onClick={() => sync('push')} disabled={Boolean(syncing)} className="certificate-secondary" aria-live="polite">
                            <i className={`bx ${syncing === 'push' ? 'bx-loader-alt bx-spin' : 'bx-cloud-upload'}`} aria-hidden />
                            {syncing === 'push' ? 'Mengirim…' : 'Kirim ke SSO'}
                        </button>
                        <button type="button" onClick={() => sync('pull')} disabled={Boolean(syncing)} className="certificate-secondary" aria-live="polite">
                            <i className={`bx ${syncing === 'pull' ? 'bx-loader-alt bx-spin' : 'bx-cloud-download'}`} aria-hidden />
                            {syncing === 'pull' ? 'Mengambil…' : 'Ambil dari SSO'}
                        </button>
                    </>}
                />
                <DataTable
                    refreshKey={tableKey}
                    className="course-index-table admin-crud-table certificate-table"
                    url={route('admin.certificate.index')}
                    columns={columns}
                    params={filterParams}
                    hideSearch
                    toolbarFilters={<div className="certificate-toolbar">
                        <label className="certificate-search">
                            <span className="sr-only">Cari peserta atau kelas</span>
                            <i className="bx bx-search" aria-hidden />
                            <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} className="admin-search-input" placeholder="Cari peserta atau kelas..." />
                        </label>
                        <div className="certificate-filters" aria-label="Filter sertifikat">
                            <label><span>Status</span><select value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">Semua status</option>
                                <option value="tidak_tersedia">Tidak tersedia</option>
                                <option value="menunggu">Tidak tersedia — Menunggu</option>
                                <option value="dikirim">Tidak tersedia — Dikirim SSO</option>
                                <option value="tersedia">Tersedia</option>
                            </select></label>
                            <label><span>Kelas</span><select value={courseId} onChange={(e) => setCourseId(e.target.value)}>
                                <option value="">Semua kelas</option>
                                {courses.map((c) => <option key={c.id} value={c.id}>{c.title}</option>)}
                            </select></label>
                            <label><span>Selesai dari</span><input type="date" value={dateFrom} max={dateTo || undefined} onChange={(e) => setDateFrom(e.target.value)} /></label>
                            <label><span>Selesai sampai</span><input type="date" value={dateTo} min={dateFrom || undefined} onChange={(e) => setDateTo(e.target.value)} /></label>
                            <button type="button" className="certificate-secondary" disabled={!search && !status && !courseId && !dateFrom && !dateTo} onClick={() => { setStatus(''); setCourseId(''); setDateFrom(''); setDateTo(''); setSearch(''); }}>
                                <i className="bx bx-reset" aria-hidden /> Reset filter
                            </button>
                        </div>
                    </div>}
                    dataLabel="sertifikat"
                    emptyTitle="Sertifikat tidak ditemukan"
                    emptyDescription="Ubah filter atau sinkronkan data sertifikat dengan SSO."
                />
            </div>
            <Modal show={Boolean(preview)} onClose={() => setPreview(null)}>
                <div className="p-4">
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <DialogTitle className="font-poppins font-semibold text-[#075D5E]">Pratinjau sertifikat</DialogTitle>
                        <button type="button" onClick={() => setPreview(null)} className={btnSecondarySm}>Tutup</button>
                    </div>
                    <p className="mb-3 text-sm">Jika preview tidak tampil, <a href={preview || '#'} target="_blank" rel="noopener noreferrer" className="underline">buka sertifikat di tab baru</a>.</p>
                    {preview && <iframe src={preview} title="Preview sertifikat peserta" className="h-[65vh] w-full rounded border" referrerPolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-popups" />}
                </div>
            </Modal>
        </AdminLayout>
    );
}
