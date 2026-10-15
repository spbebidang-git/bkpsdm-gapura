import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import '@/../css/admin-course-index.css';

export default function Index({ lastSyncedAt }) {
    const busy = useRef(false);
    const [tableKey, setTableKey] = useState(0);

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
                accessorKey: 'code',
                header: 'Kode',
                cell: ({ getValue }) => {
                    const v = getValue();
                    if (!v) return <span className="text-[#C2C9D6]">—</span>;
                    return (
                        <span className="inline-flex items-center rounded-[6px] bg-[#DAF1FE] px-2 py-0.5 text-[12px] font-medium tabular-nums text-[#2E5D6E]">
                            {v}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'title',
                header: 'Judul',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'synced_at',
                header: 'Disinkronkan',
                cell: ({ getValue }) => {
                    const v = getValue();
                    if (!v || v === '-') return <span className="text-[#C2C9D6]">—</span>;
                    return <span className="tabular-nums text-[#526176]">{v}</span>;
                },
            },
        ],
        []
    );

    const sync = () => {
        if (busy.current) return;
        busy.current = true;
        router.post(
            route('admin.category.sync'),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setTableKey((k) => k + 1),
                onFinish: () => {
                    busy.current = false;
                },
            }
        );
    };

    return (
        <AdminLayout title="Kategori" hideTitle>
            <Head title="Kategori" />
            <div className="course-index">
                <CrudPageHeader title="Kategori" className="course-index-header" description={
                        <>
                            Data kategori diambil dari Prestige. Sinkronisasi terakhir:{' '}
                            <span className="font-medium text-[#003C52]">
                                {lastSyncedAt || '—'}
                            </span>
                        </>
                    } actions={<button
                        type="button"
                        onClick={sync}
                        className="course-index-create"
                    >
                        <i className="bx bx-refresh" aria-hidden />
                        Sinkronkan Prestige
                    </button>} />
                <DataTable
                    url={route('admin.category.index')}
                    columns={columns}
                    refreshKey={tableKey}
                    className="course-index-table admin-crud-table"
                    dataLabel="kategori"
                    searchPlaceholder="Cari kode atau kategori..."
                    emptyTitle="Belum ada kategori"
                    emptyDescription="Jalankan sinkronisasi untuk mengambil kategori dari Prestige."
                />
            </div>
        </AdminLayout>
    );
}
