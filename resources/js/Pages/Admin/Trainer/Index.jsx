import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import DataTable from '@/Components/Admin/DataTable';
import {
    badgeOk,
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
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Link href={route('admin.trainer.edit', getValue())} className={btnEdit}>
                            Edit
                        </Link>
                        <button
                            type="button"
                            onClick={() =>
                                confirmDelete(route('admin.trainer.destroy', getValue()), {
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
        <AdminLayout title="Trainer" hideTitle>
            <Head title="Trainer" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Pembelajaran</p>
                        <h1 className={pageTitle}>Trainer</h1>
                        <p className={pageDescription}>
                            Data trainer yang dapat dipilih pada setiap kelas.
                        </p>
                    </div>
                    <Link href={route('admin.trainer.create')} className={`${btnPrimarySm} shrink-0`}>
                        <i className="bx bx-plus text-base" />
                        Trainer baru
                    </Link>
                </div>
                <DataTable
                    key={tableKey}
                    url={route('admin.trainer.index')}
                    columns={columns}
                    searchPlaceholder="Cari trainer..."
                    emptyTitle="Belum ada trainer"
                    emptyDescription="Tambahkan data trainer terlebih dahulu."
                />
            </div>
        </AdminLayout>
    );
}
