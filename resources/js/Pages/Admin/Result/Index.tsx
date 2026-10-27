import DataTable from '@/Components/Admin/DataTable';
import { badgeInfo, btnEdit, pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
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
                accessorKey: 'title',
                header: 'Kelas',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                id: 'aksi',
                accessorKey: 'slug',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Link href={route('admin.result.show', getValue())} className={btnEdit}>
                            Detail
                        </Link>
                        <Link
                            href={route('admin.result.post-test', getValue())}
                            className={badgeInfo}
                        >
                            Post Test
                        </Link>
                    </div>
                ),
            },
        ],
        []
    );

    return (
        <AdminLayout title="Hasil" hideTitle>
            <Head title="Hasil" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Pembelajaran</p>
                        <h1 className={pageTitle}>Hasil belajar</h1>
                        <p className={pageDescription}>Nilai pengguna dan ringkasan post-test untuk setiap kelas.</p>
                    </div>
                </div>
                <DataTable
                    url={route('admin.result.index')}
                    columns={columns}
                    searchPlaceholder="Cari kelas..."
                    emptyTitle="Belum ada hasil belajar"
                    emptyDescription="Hasil akan tersedia setelah pengguna mengikuti kegiatan."
                />
            </div>
        </AdminLayout>
    );
}
