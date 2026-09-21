import DataTable from '@/Components/Admin/DataTable';
import { badgePending, pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Index() {
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
        ],
        []
    );

    return (
        <AdminLayout title="Peran" hideTitle>
            <Head title="Peran" />
            <div className="space-y-6">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Sistem</p>
                        <h1 className={pageTitle}>Peran</h1>
                        <p className={pageDescription}>Peran yang menentukan cakupan akses pengguna admin.</p>
                    </div>
                </div>
                <DataTable
                    url={route('admin.management.role.index')}
                    columns={columns}
                    searchPlaceholder="Cari peran..."
                    emptyTitle="Belum ada peran"
                    emptyDescription="Peran sistem akan tampil di sini."
                />
            </div>
        </AdminLayout>
    );
}
