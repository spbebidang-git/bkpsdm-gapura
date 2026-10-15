import DataTable from '@/Components/Admin/DataTable';
import { RowActions } from '@/Components/Admin/ActionButtons';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-employee-status.css';
import '@/../css/admin-course-index.css';

type StatusRow = { id: string; title: string };

export default function Index() {
    const [refreshKey, setRefreshKey] = useState(0);
    const columns = useMemo(() => [
        { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => <span>{table.getState().pagination.pageIndex * table.getState().pagination.pageSize + row.index + 1}</span> },
        { accessorKey: 'title', header: 'Status pegawai', cell: ({ getValue }) => <span className="employee-status-name">{getValue()}</span> },
        {
            id: 'aksi', accessorKey: 'id', header: 'Aksi', enableSorting: false, enableGlobalFilter: false,
            meta: { align: 'right' },
            cell: ({ getValue, row }) => <RowActions
                editHref={route('admin.employee-status.edit', getValue())}
                deleteUrl={route('admin.employee-status.destroy', getValue())}
                deleteMessage={`Status “${(row.original as StatusRow).title}” akan dihapus.`}
                onDeleted={() => setRefreshKey((key) => key + 1)}
                iconOnly
                variant="course"
            />,
        },
    ], []);

    return <AdminLayout title="Status pegawai" hideTitle>
        <Head title="Status pegawai" />
        <div className="employee-status-admin course-index">
            <CrudPageHeader title="Status pegawai" description="Kelola daftar status kepegawaian yang dapat dipilih pada kelas dan peserta." actions={
                <Link href={route('admin.employee-status.create')} className="course-index-create"><i className="bx bx-plus" aria-hidden />Tambah status</Link>
            } />
            <DataTable
                url={route('admin.employee-status.index')}
                columns={columns}
                refreshKey={refreshKey}
                className="course-index-table admin-crud-table employee-status-table"
                searchPlaceholder="Cari status pegawai..."
                emptyTitle="Belum ada status pegawai"
                emptyDescription="Tambahkan status kepegawaian untuk digunakan pada kelas dan peserta."
                dataLabel="Status pegawai"
            />
        </div>
    </AdminLayout>;
}
