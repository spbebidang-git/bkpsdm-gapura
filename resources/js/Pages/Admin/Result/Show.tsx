import DataTable from '@/Components/Admin/DataTable';
import { btnLink, btnPrimarySm, pageDescription, pageEyebrow, pageHeader, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Show({ course, names = [], activities = [], filters = {} as any }) {
    const [nama, setNama] = useState(filters.nama || '');
    const [kegiatan, setKegiatan] = useState(filters.kegiatan || '');
    const [applied, setApplied] = useState({
        nama: filters.nama || '',
        kegiatan: filters.kegiatan || '',
    });

    const filterParams = useMemo(
        () => ({ nama: applied.nama, kegiatan: applied.kegiatan }),
        [applied]
    );

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
                accessorKey: 'kegiatan',
                header: 'Kegiatan',
                cell: ({ getValue }) => (
                    <span className="text-[#526176]">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'nilai',
                header: 'Nilai',
                enableSorting: false,
                enableGlobalFilter: false,
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
        <AdminLayout title={`Hasil: ${course.title}`} hideTitle>
            <Head title={`Hasil ${course.title}`} />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Hasil belajar</p>
                        <h1 className={pageTitle}>{course.title}</h1>
                        <p className={pageDescription}>Nilai setiap peserta berdasarkan kegiatan yang diikuti.</p>
                    </div>
                    <Link href={route('admin.result.index')} className={`${btnLink} shrink-0`}>
                        ← Kembali
                    </Link>
                </div>

                <form noValidate
                    onSubmit={(e) => {
                        e.preventDefault();
                        setApplied({ nama, kegiatan });
                    }}
                    className="flex flex-wrap items-end gap-3"
                >
                    <select
                        value={nama}
                        onChange={(e) => setNama(e.target.value)}
                        className="form-control max-w-xs"
                    >
                        <option value="">Semua peserta</option>
                        {names.map((n) => (
                            <option key={n.id} value={n.id}>
                                {n.name}
                            </option>
                        ))}
                    </select>
                    <select
                        value={kegiatan}
                        onChange={(e) => setKegiatan(e.target.value)}
                        className="form-control max-w-xs"
                    >
                        <option value="">Semua kegiatan</option>
                        {activities.map((a) => (
                            <option key={a.id} value={a.id}>
                                {a.title}
                            </option>
                        ))}
                    </select>
                    <button type="submit" className={btnPrimarySm}>
                        Filter
                    </button>
                </form>

                <DataTable
                    key={`${applied.nama}-${applied.kegiatan}`}
                    url={route('admin.result.show', course.slug)}
                    columns={columns}
                    params={filterParams}
                    searchPlaceholder="Cari NIP, peserta, atau kegiatan..."
                    emptyTitle="Hasil tidak ditemukan"
                    emptyDescription="Ubah filter atau tunggu peserta menyelesaikan kegiatan."
                />
            </div>
        </AdminLayout>
    );
}
