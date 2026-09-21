import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import DataTable from '@/Components/Admin/DataTable';
import {
    badgePending,
    btnDanger,
    btnEdit,
    btnPrimarySm,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Index() {
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
                accessorKey: 'name',
                header: 'Nama',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'guard_name',
                header: 'Guard',
                cell: ({ getValue }) => (
                    <span className={badgePending}>{getValue() || '—'}</span>
                ),
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Link
                            href={route('admin.management.permission.edit', getValue())}
                            className={btnEdit}
                        >
                            Edit
                        </Link>
                        <button
                            type="button"
                            onClick={() =>
                                confirmDelete(
                                    route('admin.management.permission.destroy', getValue()),
                                    { onSuccess: () => setTableKey((k) => k + 1) }
                                )
                            }
                            className={btnDanger}
                        >
                            Hapus
                        </button>
                    </div>
                ),
            },
        ],
        []
    );

    return (
        <AdminLayout title="Hak Akses" hideTitle>
            <Head title="Hak Akses" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Sistem</p>
                        <h1 className={pageTitle}>Hak akses</h1>
                        <p className={pageDescription}>Daftar izin yang dapat diberikan kepada peran admin.</p>
                    </div>
                    <div className="shrink-0">
                        <Link
                            href={route('admin.management.permission.create')}
                            className={btnPrimarySm}
                        >
                            <i className="bx bx-plus text-base" />
                            Hak akses baru
                        </Link>
                    </div>
                </div>
                <DataTable
                    key={tableKey}
                    url={route('admin.management.permission.index')}
                    columns={columns}
                    searchPlaceholder="Cari hak akses..."
                    emptyTitle="Belum ada hak akses"
                    emptyDescription="Tambahkan izin yang diperlukan untuk peran admin."
                />
            </div>
        </AdminLayout>
    );
}
