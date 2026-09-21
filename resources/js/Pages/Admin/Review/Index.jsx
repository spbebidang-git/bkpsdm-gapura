import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import DataTable from '@/Components/Admin/DataTable';
import { btnDanger, pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function Stars({ value }) {
    const n = Math.max(0, Math.min(5, Number(value) || 0));
    return (
        <span className="inline-flex items-center gap-0.5" aria-label={`${n} bintang`}>
            {Array.from({ length: 5 }, (_, i) => (
                <i
                    key={i}
                    className={`bx bxs-star text-base ${i < n ? 'text-[#F5A623]' : 'text-[#E8ECF1]'}`}
                />
            ))}
        </span>
    );
}

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
                accessorKey: 'course',
                header: 'Kelas',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'user',
                header: 'Peserta',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'star',
                header: 'Bintang',
                cell: ({ getValue }) => <Stars value={getValue()} />,
            },
            {
                accessorKey: 'description',
                header: 'Ulasan',
                cell: ({ getValue }) => {
                    const v = getValue();
                    if (!v) return <span className="text-[#C2C9D6]">—</span>;
                    return (
                        <span className="text-left text-[#526176] line-clamp-2 max-w-md block">
                            {v}
                        </span>
                    );
                },
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <button
                        type="button"
                        onClick={() =>
                            confirmDelete(route('admin.review.destroy', getValue()), {
                                onSuccess: () => setTableKey((k) => k + 1),
                            })
                        }
                        className={btnDanger}
                    >
                        Hapus
                    </button>
                ),
            },
        ],
        []
    );

    return (
        <AdminLayout title="Ulasan" hideTitle>
            <Head title="Ulasan" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Operasional</p>
                        <h1 className={pageTitle}>Ulasan</h1>
                        <p className={pageDescription}>Nilai bintang dan komentar peserta untuk setiap kelas.</p>
                    </div>
                </div>
                <DataTable
                    key={tableKey}
                    url={route('admin.review.index')}
                    columns={columns}
                    searchPlaceholder="Cari kelas atau peserta..."
                    emptyTitle="Belum ada ulasan"
                    emptyDescription="Ulasan peserta akan tampil di sini."
                />
            </div>
        </AdminLayout>
    );
}
