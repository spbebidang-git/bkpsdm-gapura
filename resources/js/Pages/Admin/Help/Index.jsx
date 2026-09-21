import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import DataTable from '@/Components/Admin/DataTable';
import {
    badgeOk,
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
                cell: ({ getValue }) => (
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Link href={route('admin.help.edit', getValue())} className={btnEdit}>
                            Edit
                        </Link>
                        <button
                            type="button"
                            onClick={() =>
                                confirmDelete(route('admin.help.destroy', getValue()), {
                                    onSuccess: () => setTableKey((k) => k + 1),
                                })
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
        <AdminLayout title="Bantuan" hideTitle>
            <Head title="Bantuan" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Operasional</p>
                        <h1 className={pageTitle}>Bantuan</h1>
                        <p className={pageDescription}>Pertanyaan dan jawaban yang tampil di portal peserta.</p>
                    </div>
                    <Link href={route('admin.help.create')} className={`${btnPrimarySm} shrink-0`}>
                        <i className="bx bx-plus text-base" />
                        Pertanyaan baru
                    </Link>
                </div>
                <DataTable
                    key={tableKey}
                    url={route('admin.help.index')}
                    columns={columns}
                    searchPlaceholder="Cari pertanyaan..."
                    emptyTitle="Belum ada bantuan"
                    emptyDescription="Tambahkan pertanyaan yang sering diajukan peserta."
                />
            </div>
        </AdminLayout>
    );
}
