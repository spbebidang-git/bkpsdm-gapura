import DataTable from '@/Components/Admin/DataTable';
import {
    badgeOk,
    badgePending,
    btnDanger,
    btnPrimarySm,
    btnXs,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
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
                header: 'Diklat',
                cell: ({ getValue }) => (
                    <span className="font-medium text-[#003C52]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'type',
                header: 'Tipe',
                cell: ({ getValue }) => {
                    const t = String(getValue() || '').toLowerCase();
                    const label = t === 'scheduled' || t === 'terjadwal' ? 'Terjadwal' : 'Tidak Terjadwal';
                    return <span className="text-[#526176]">{label}</span>;
                },
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ getValue }) => {
                    const s = String(getValue() || '');
                    const label = s === 'publish' ? 'Aktif' : s === 'draft' ? 'Draft' : s;
                    return <span className={s === 'publish' ? badgeOk : badgePending}>{label}</span>;
                },
            },
            {
                accessorKey: 'updated_at',
                header: 'Diperbarui',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                id: 'aksi',
                accessorKey: 'slug',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ getValue }) => (
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.course.show', getValue())} className={btnXs}>
                            Detail
                        </Link>
                        <Link href={route('admin.course.edit', getValue())} className={btnXs}>
                            Edit
                        </Link>
                        <Link
                            href={route('admin.course.destroy', getValue())}
                            method="delete"
                            className={btnDanger}
                        >
                            Hapus
                        </Link>
                    </div>
                ),
            },
        ],
        []
    );

    return (
        <AdminLayout title="Kelas" hideTitle>
            <Head title="Kelas" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Pembelajaran</p>
                        <h1 className={pageTitle}>Kelas</h1>
                        <p className={pageDescription}>Daftar kelas beserta tipe, status, dan waktu pembaruannya.</p>
                    </div>
                    <Link href={route('admin.course.create')} className={`${btnPrimarySm} shrink-0`}>
                        Buat kelas
                    </Link>
                </div>
                <DataTable
                    url={route('admin.course.index')}
                    columns={columns}
                    searchPlaceholder="Cari judul kelas..."
                    emptyTitle="Belum ada kelas"
                    emptyDescription="Buat kelas pertama untuk mulai menyusun pembelajaran."
                />
            </div>
        </AdminLayout>
    );
}
