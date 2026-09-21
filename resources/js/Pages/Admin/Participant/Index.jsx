import DataTable from '@/Components/Admin/DataTable';
import { badgeInfo, pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';

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
        <span className={badgeInfo}>{value}</span>
    );
}

export default function Index() {
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
        ],
        []
    );

    return (
        <AdminLayout title="Peserta" hideTitle>
            <Head title="Peserta" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Pembelajaran</p>
                        <h1 className={pageTitle}>Peserta</h1>
                        <p className={pageDescription}>Data peserta pelatihan berdasarkan NIP, status pegawai, dan jabatan.</p>
                    </div>
                </div>
                <DataTable
                    url={route('admin.participant.index')}
                    columns={columns}
                    searchPlaceholder="Cari NIP atau nama peserta..."
                    emptyTitle="Belum ada peserta"
                    emptyDescription="Data peserta akan tampil setelah tersedia di sistem."
                />
            </div>
        </AdminLayout>
    );
}
