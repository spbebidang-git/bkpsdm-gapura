import DataTable from '@/Components/Admin/DataTable';
import { RowActions } from '@/Components/Admin/ActionButtons';
import { badgePending } from '@/Components/Admin/ui';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-index.css';

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
                meta: { align: 'right' },
                cell: ({ getValue }) => <RowActions
                    editHref={route('admin.management.permission.edit', getValue())}
                    deleteUrl={route('admin.management.permission.destroy', getValue())}
                    deleteMessage="Hak akses ini akan dihapus."
                    onDeleted={() => setTableKey((key) => key + 1)}
                    iconOnly
                    variant="course"
                />,
            },
        ],
        []
    );

    return (
        <AdminLayout title="Hak Akses" hideTitle>
            <Head title="Hak Akses" />
            <div className="admin-index-page">
                <CrudPageHeader title="Hak akses" description="Daftar izin yang dapat diberikan kepada peran admin." actions={
                    <Link href={route('admin.management.permission.create')} className="admin-index-create">
                        <i className="bx bx-plus" aria-hidden />Hak akses baru
                    </Link>
                } />
                <DataTable
                    key={tableKey}
                    url={route('admin.management.permission.index')}
                    columns={columns}
                    className="admin-crud-table admin-index-table"
                    searchPlaceholder="Cari hak akses..."
                    emptyTitle="Belum ada hak akses"
                    emptyDescription="Tambahkan izin yang diperlukan untuk peran admin."
                />
            </div>
        </AdminLayout>
    );
}
