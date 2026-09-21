import DataTable from '@/Components/Admin/DataTable';
import { btnLink, pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { useMemo } from 'react';

export default function PostTest({ course }) {
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
                accessorKey: 'nip',
                header: 'NIP',
                cell: ({ getValue }) => (
                    <span className="tabular-nums text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'nama',
                header: 'Nama',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'jabatan',
                header: 'Jabatan',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'rata_rata_nilai',
                header: 'Rata-rata',
                cell: ({ getValue }) => (
                    <span className="tabular-nums font-medium text-[#003C52]">
                        {getValue() ?? '—'}
                    </span>
                ),
            },
        ],
        []
    );

    return (
        <AdminLayout title={`Post Test: ${course.title}`} hideTitle>
            <Head title={`Post Test ${course.title}`} />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Post-test</p>
                        <h1 className={pageTitle}>{course.title}</h1>
                        <p className={pageDescription}>Rata-rata nilai post-test setiap peserta.</p>
                    </div>
                    <Link href={route('admin.result.index')} className={`${btnLink} shrink-0`}>
                        ← Kembali
                    </Link>
                </div>
                <DataTable
                    url={route('admin.result.post-test', course.slug)}
                    columns={columns}
                    searchPlaceholder="Cari NIP atau nama peserta..."
                    emptyTitle="Belum ada hasil post-test"
                    emptyDescription="Nilai akan tampil setelah peserta menyelesaikan post-test."
                />
            </div>
        </AdminLayout>
    );
}
