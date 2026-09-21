import { CreateButton, RowActions } from '@/Components/Admin/ActionButtons';
import DataTable from '@/Components/Admin/DataTable';
import { pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
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
                accessorKey: 'email',
                header: 'Email',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <RowActions
                        editHref={route('admin.management.user.edit', getValue())}
                        deleteUrl={route('admin.management.user.destroy', getValue())}
                        onDeleted={() => setTableKey((k) => k + 1)}
                    />
                ),
            },
        ],
        []
    );

    return (
        <AdminLayout title="Pengguna Admin" hideTitle>
            <Head title="Pengguna Admin" />
            <div className="space-y-6">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Sistem</p>
                        <h1 className={pageTitle}>Pengguna admin</h1>
                        <p className={pageDescription}>Akun yang dapat mengakses dan mengelola panel admin.</p>
                    </div>
                    <div className="shrink-0">
                        <CreateButton
                            href={route('admin.management.user.create')}
                            label="Pengguna baru"
                            icon="bx-plus"
                        />
                    </div>
                </div>
                <DataTable
                    key={tableKey}
                    url={route('admin.management.user.index')}
                    columns={columns}
                    searchPlaceholder="Cari nama atau email..."
                    emptyTitle="Belum ada pengguna admin"
                    emptyDescription="Tambahkan akun untuk memberi akses ke panel admin."
                />
            </div>
        </AdminLayout>
    );
}
