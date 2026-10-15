import DataTable from '@/Components/Admin/DataTable';
import { CreateButton, RowActions } from '@/Components/Admin/ActionButtons';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-course-index.css';

export default function Index({ opds = [], isSuperAdmin = false }) {
    const [opdId, setOpdId] = useState('');
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
                accessorKey: 'photo_url',
                header: 'Foto',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <img
                        src={getValue() || '/assets/media/images/static/default-class.jpg'}
                        alt="Foto trainer"
                        className="h-10 w-10 rounded-full border border-hairline object-cover"
                    />
                ),
            },
            {
                accessorKey: 'name',
                header: 'Nama',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52] text-left block max-w-xl">
                        {getValue() || '—'}
                    </span>
                ),
            },
            {
                accessorKey: 'jabatan',
                header: 'Jabatan',
                enableSorting: false,
                cell: ({ getValue }) => (
                    <span className="text-left block max-w-sm">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'source',
                header: 'Sumber',
                cell: ({ getValue, row }) => <span className="admin-crud-status is-inactive">{row.original.source_label || (getValue() === 'sso' ? 'Kedaton SSO' : 'Lokal')}</span>,
            },
            {
                accessorKey: 'opd_name',
                header: 'OPD',
                enableSorting: false,
                cell: ({ getValue }) => <span>{getValue() || 'Belum terhubung ke OPD'}</span>,
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                meta: { align: 'right' },
                cell: ({ getValue, row }) => (
                    <RowActions editHref={row.original.user_id
                        ? (isSuperAdmin ? route('admin.trainer.edit', getValue()) : undefined)
                        : route('admin.trainer.edit', getValue())} deleteUrl={route('admin.trainer.destroy', getValue())} deleteMessage={`Trainer “${row.original.name}” akan dihapus.${row.original.source === 'sso' ? ' Role trainer dan akses trainer pengguna akan dicabut.' : ''}`} onDeleted={() => setTableKey((k) => k + 1)} iconOnly variant="course" />
                ),
            },
        ],
        [isSuperAdmin]
    );

    return (
        <AdminLayout title="Trainer" hideTitle>
            <Head title="Trainer" />
            <div className="course-index">
                <CrudPageHeader
                    title="Trainer"
                    description="Data trainer yang dapat dipilih pada setiap kelas."
                    className="course-index-header"
                    actions={<div className="flex flex-wrap gap-2">
                        <CreateButton href={route('admin.trainer.create')} label="Trainer baru" className="course-index-create" />
                        <CreateButton href={route('admin.trainer.create', { mode: 'user' })} label="Daftarkan pengguna" icon="bx-user-plus" className="course-index-create" />
                    </div>}
                />
                <DataTable
                    params={{ opd_id: opdId }}
                    url={route('admin.trainer.index')}
                    columns={columns}
                    toolbarFilters={(
                        <label className="course-trainer-filter">
                            <span className="sr-only">Filter trainer berdasarkan OPD</span>
                            <select
                                value={opdId}
                                onChange={(event) => setOpdId(event.target.value)}
                                aria-label="Filter trainer berdasarkan OPD"
                            >
                                <option value="">Semua OPD</option>
                                {opds.map((opd) => (
                                    <option key={opd.id} value={opd.id}>{opd.name}</option>
                                ))}
                            </select>
                        </label>
                    )}
                    refreshKey={tableKey}
                    className="course-index-table admin-crud-table"
                    dataLabel="trainer"
                    searchPlaceholder="Cari trainer..."
                    emptyTitle="Belum ada trainer"
                    emptyDescription="Tambahkan data trainer terlebih dahulu."
                />
            </div>
        </AdminLayout>
    );
}
