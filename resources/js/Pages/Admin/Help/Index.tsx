import DataTable from '@/Components/Admin/DataTable';
import { RowActions } from '@/Components/Admin/ActionButtons';
import { badgeOk, badgePending } from '@/Components/Admin/ui';
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
                accessorKey: 'question',
                header: 'Pertanyaan',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52] text-left block max-w-xl">
                        {getValue() || '—'}
                    </span>
                ),
            },
            {
                accessorKey: 'is_active',
                header: 'Status',
                enableSorting: false,
                cell: ({ getValue }) =>
                    getValue() ? (
                        <span className={badgeOk}>Aktif</span>
                    ) : (
                        <span className={badgePending}>Nonaktif</span>
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
                    editHref={route('admin.help.edit', getValue())}
                    deleteUrl={route('admin.help.destroy', getValue())}
                    deleteMessage="Pertanyaan bantuan ini akan dihapus."
                    onDeleted={() => setTableKey((key) => key + 1)}
                    iconOnly
                    variant="course"
                />,
            },
        ],
        []
    );

    return (
        <AdminLayout title="Bantuan" hideTitle>
            <Head title="Bantuan" />
            <div className="admin-index-page">
                <CrudPageHeader title="Bantuan" description="Pertanyaan dan jawaban yang tampil di portal peserta." actions={
                    <Link href={route('admin.help.create')} className="admin-index-create">
                        <i className="bx bx-plus text-base" />
                        Pertanyaan baru
                    </Link>
                } />
                <DataTable
                    key={tableKey}
                    url={route('admin.help.index')}
                    columns={columns}
                    className="admin-crud-table admin-index-table"
                    searchPlaceholder="Cari pertanyaan..."
                    emptyTitle="Belum ada bantuan"
                    emptyDescription="Tambahkan pertanyaan yang sering diajukan peserta."
                />
            </div>
        </AdminLayout>
    );
}
