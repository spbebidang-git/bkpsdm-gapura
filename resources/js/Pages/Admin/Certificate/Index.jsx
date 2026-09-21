import DataTable from '@/Components/Admin/DataTable';
import {
    badgeInfo,
    badgeOk,
    badgePending,
    btnPrimarySm,
    btnSecondarySm,
    btnXs,
    card,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';

export default function Index({ courses = [] }) {
    const [status, setStatus] = useState('');
    const [courseId, setCourseId] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [tableKey, setTableKey] = useState(0);
    const processing = useRef(false);

    const filterParams = useMemo(
        () => ({
            status_filter: status,
            course_filter: courseId,
            date_from: dateFrom,
            date_to: dateTo,
        }),
        [status, courseId, dateFrom, dateTo]
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
                header: 'Nama',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'satker',
                header: 'Satker',
                enableSorting: false,
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'course',
                header: 'Kelas',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'finished_at',
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
                        available: badgeOk,
                        sent: badgeInfo,
                        pending: badgePending,
                    };
                    return (
                        <span className={map[s] || badgePending}>
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
                    if (!url || url === '-' || url === '') {
                        return <span className="text-[#C2C9D6]">—</span>;
                    }
                    return (
                        <a href={url} target="_blank" rel="noreferrer" className={btnXs}>
                            Unduh
                        </a>
                    );
                },
            },
        ],
        []
    );

    const sync = (type) => {
        if (processing.current) return;
        processing.current = true;
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
                },
            }
        );
    };

    return (
        <AdminLayout title="Sertifikat" hideTitle>
            <Head title="Sertifikat" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Operasional</p>
                        <h1 className={pageTitle}>Sertifikat</h1>
                        <p className={pageDescription}>Status penerbitan sertifikat peserta dan sinkronisasi dengan SSO.</p>
                    </div>
                    <div className="shrink-0">
                        <div className="flex shrink-0 flex-wrap gap-2">
                            <button type="button" onClick={() => sync('push')} className={btnPrimarySm}>
                                Kirim ke SSO
                            </button>
                            <button
                                type="button"
                                onClick={() => sync('pull')}
                                className={btnSecondarySm}
                            >
                                Ambil dari SSO
                            </button>
                        </div>
                    </div>
                </div>

                <div className={`${card} grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4`} aria-label="Filter sertifikat">
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="form-control"
                        aria-label="Filter status"
                    >
                        <option value="">Semua status</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="dikirim">Dikirim</option>
                        <option value="tersedia">Tersedia</option>
                    </select>
                    <select
                        value={courseId}
                        onChange={(e) => setCourseId(e.target.value)}
                        className="form-control"
                        aria-label="Filter kelas"
                    >
                        <option value="">Semua kelas</option>
                        {courses.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.title}
                            </option>
                        ))}
                    </select>
                    <input
                        type="date"
                        value={dateFrom}
                        onChange={(e) => setDateFrom(e.target.value)}
                        className="form-control"
                        aria-label="Tanggal mulai"
                    />
                    <input
                        type="date"
                        value={dateTo}
                        onChange={(e) => setDateTo(e.target.value)}
                        className="form-control"
                        aria-label="Tanggal akhir"
                    />
                </div>

                <DataTable
                    key={tableKey}
                    url={route('admin.certificate.index')}
                    columns={columns}
                    params={filterParams}
                    searchPlaceholder="Cari peserta, satker, atau kelas..."
                    emptyTitle="Sertifikat tidak ditemukan"
                    emptyDescription="Ubah filter atau sinkronkan data sertifikat dengan SSO."
                />
            </div>
        </AdminLayout>
    );
}
