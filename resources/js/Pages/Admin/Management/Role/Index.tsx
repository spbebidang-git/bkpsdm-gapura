import DataTable from '@/Components/Admin/DataTable';
import { CreateButton, RowActions } from '@/Components/Admin/ActionButtons';
import { badgePending } from '@/Components/Admin/ui';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-index.css';

const NON_DELETABLE_ROLES = ['super_admin'];

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
                cell: ({ row, getValue }) => row.original.name === 'super_admin'
                    ? <span className="text-xs font-medium text-[#8496B0]">Akses penuh</span>
                    : <RowActions
                        editHref={route('admin.management.role.edit', getValue())}
                        deleteUrl={NON_DELETABLE_ROLES.includes(row.original.name) ? null : route('admin.management.role.destroy', getValue())}
                        deleteMessage={`Hapus role ${row.original.name}? Pengguna yang memakai role ini akan kehilangan akses terkait.`}
                        onDeleted={() => setTableKey((key) => key + 1)}
                        iconOnly
                        variant="course"
                    />,
            },
        ],
        []
    );

    return (
        <AdminLayout title="Peran" hideTitle>
            <Head title="Peran" />
            <div className="admin-index-page">
                <CrudPageHeader title="Peran" description="Peran yang menentukan cakupan akses pengguna admin." actions={
                    <CreateButton href={route('admin.management.role.create')} label="Role baru" icon="bx-plus" />
                } />
                <DataTable
                    key={tableKey}
                    url={route('admin.management.role.index')}
                    columns={columns}
                    className="admin-crud-table admin-index-table"
                    searchPlaceholder="Cari peran..."
                    emptyTitle="Belum ada peran"
                    emptyDescription="Peran sistem akan tampil di sini."
                />
            </div>
        </AdminLayout>
    );
}
