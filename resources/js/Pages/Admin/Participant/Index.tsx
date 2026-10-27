import DataTable from '@/Components/Admin/DataTable';
import { CreateButton, RowActions } from '@/Components/Admin/ActionButtons';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-course-index.css';

function Avatar({ name }) {
    const initial = (name || '?').charAt(0).toUpperCase();
    return (
        <span
            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-white"
            aria-hidden
        >
            {initial}
        </span>
    );
}

function StatusChip({ value }) {
    if (!value) {
        return <span className="text-[#C2C9D6]">—</span>;
    }
    return (
        <span className="participant-status-chip">{value}</span>
    );
}

export default function Index() {
    const [refreshKey, setRefreshKey] = useState(0);
    const columns = useMemo(
        () => [
            {
                id: 'DT_RowIndex',
                accessorKey: 'DT_RowIndex',
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
                accessorKey: 'nip',
                header: 'NIP',
                cell: ({ getValue }) => {
                    const nip = getValue();
                    if (!nip) {
                        return <span className="text-[#C2C9D6]">—</span>;
                    }
                    return (
                        <span className="inline-flex items-center rounded-[6px] bg-[#DAF1FE] px-2 py-0.5 text-[12px] font-medium tabular-nums text-[#2E5D6E]">
                            {nip}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'name',
                header: 'Nama',
                cell: ({ getValue }) => {
                    const name = getValue();
                    return (
                        <div className="inline-flex items-center justify-center gap-3 max-w-full">
                            <Avatar name={name} />
                            <span className="font-medium text-[#003C52] truncate text-left">
                                {name || '—'}
                            </span>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'status_pegawai',
                header: 'Status Pegawai',
                cell: ({ getValue }) => <StatusChip value={getValue()} />,
            },
            {
                accessorKey: 'jabatan',
                header: 'Jabatan',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'opd',
                header: 'OPD',
                cell: ({ getValue }) => <span className="text-[#526176]">{getValue() || '—'}</span>,
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                meta: { align: 'right' },
                cell: ({ getValue, row }) => row.original.can_manage ? <RowActions
                    editHref={route('admin.participant.edit', getValue())}
                    deleteUrl={route('admin.participant.destroy', getValue())}
                    deleteMessage={`Pengguna “${row.original.name}” akan dihapus dari Master Pengguna.`}
                    onDeleted={() => setRefreshKey((key) => key + 1)}
                    iconOnly
                    variant="course"
                /> : <span className="text-xs text-[#8496B0]">Dari sumber akun</span>,
            },
        ],
        []
    );

    return (
        <AdminLayout title="Pengguna" hideTitle>
            <Head title="Pengguna" />
            <div className="course-index">
                <CrudPageHeader title="Pengguna" description="Data pengguna dari akun terhubung dan akun lokal GAPURA." className="course-index-header" actions={<CreateButton href={route('admin.participant.create')} label="Tambah pengguna lokal" />} />
                <DataTable
                    url={route('admin.participant.index')}
                    columns={columns}
                    refreshKey={refreshKey}
                    className="course-index-table admin-crud-table participant-index-table"
                    dataLabel="pengguna"
                    searchPlaceholder="Cari NIP atau nama pengguna..."
                    emptyTitle="Belum ada pengguna"
                    emptyDescription="Data pengguna akan tampil setelah tersedia di sistem."
                />
            </div>
        </AdminLayout>
    );
}
