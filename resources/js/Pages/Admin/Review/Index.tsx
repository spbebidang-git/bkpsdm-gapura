import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-review.css';

type ReviewFilters = { course_id: string; star: string };
type CourseOption = { id: string | number; title: string };

function Stars({ value }) {
    const n = Math.max(0, Math.min(5, Number(value) || 0));
    return (
        <span className="review-stars" aria-label={`${n} dari 5 bintang`}>
            {Array.from({ length: 5 }, (_, i) => (
                <i key={i} className={`bx bxs-star ${i < n ? 'is-filled' : ''}`} aria-hidden />
            ))}
        </span>
    );
}

export default function Index({
    courses = [],
    filters: initialFilters = { course_id: '', star: '' },
}: {
    courses?: CourseOption[];
    filters?: ReviewFilters;
}) {
    const [tableKey, setTableKey] = useState(0);
    const [filters, setFilters] = useState<ReviewFilters>({
        course_id: initialFilters.course_id || '',
        star: initialFilters.star || '',
    });

    const columns = useMemo(
        () => [
            {
                id: 'DT_RowIndex',
                header: 'No',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ row, table }) => {
                    const { pageIndex, pageSize } = table.getState().pagination;
                    return <span className="tabular-nums text-stone">{pageIndex * pageSize + row.index + 1}</span>;
                },
            },
            {
                accessorKey: 'course',
                header: 'Kelas',
                cell: ({ getValue }) => <span className="review-course">{getValue() || '—'}</span>,
            },
            {
                accessorKey: 'user',
                header: 'Pengguna',
                cell: ({ getValue }) => <span className="review-participant">{getValue() || '—'}</span>,
            },
            {
                accessorKey: 'star',
                header: 'Penilaian',
                meta: { align: 'center' },
                cell: ({ getValue }) => <Stars value={getValue()} />,
            },
            {
                accessorKey: 'description',
                header: 'Ulasan',
                cell: ({ getValue }) => {
                    const value = getValue();
                    return value
                        ? <span className="review-comment">{value}</span>
                        : <span className="text-stone">Tanpa komentar</span>;
                },
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                enableSorting: false,
                enableGlobalFilter: false,
                meta: { align: 'right' },
                cell: ({ getValue }) => (
                    <button
                        type="button"
                        onClick={() => confirmDelete(route('admin.review.destroy', getValue()), {
                            onSuccess: () => setTableKey((key) => key + 1),
                        })}
                        className="review-delete"
                    >
                        <i className="bx bx-trash" aria-hidden />
                        Hapus
                    </button>
                ),
            },
        ],
        [],
    );

    const toolbarFilters = (
        <div className="review-filters" aria-label="Filter ulasan">
            <label>
                <span className="sr-only">Filter kelas</span>
                <select
                    aria-label="Filter kelas"
                    className="review-filter-course"
                    value={filters.course_id}
                    onChange={(event) => setFilters((current) => ({ ...current, course_id: event.target.value }))}
                >
                    <option value="">Semua kelas</option>
                    {courses.map((course) => <option key={course.id} value={course.id}>{course.title}</option>)}
                </select>
            </label>
            <label>
                <span className="sr-only">Filter bintang</span>
                <select
                    aria-label="Filter bintang"
                    className="review-filter-star"
                    value={filters.star}
                    onChange={(event) => setFilters((current) => ({ ...current, star: event.target.value }))}
                >
                    <option value="">Semua bintang</option>
                    {[5, 4, 3, 2, 1].map((star) => <option key={star} value={star}>{star} bintang</option>)}
                </select>
            </label>
            {(filters.course_id || filters.star) && (
                <button type="button" className="review-filter-reset" onClick={() => setFilters({ course_id: '', star: '' })}>
                    Reset filter
                </button>
            )}
        </div>
    );

    return (
        <AdminLayout title="Ulasan" hideTitle>
            <Head title="Ulasan" />
            <div className="review-admin">
                <CrudPageHeader title="Ulasan" description="Nilai bintang dan komentar pengguna untuk setiap kelas." />
                <DataTable
                    key={tableKey}
                    url={route('admin.review.index')}
                    columns={columns}
                    params={filters}
                    toolbarFilters={toolbarFilters}
                    className="admin-crud-table review-index-table"
                    searchPlaceholder="Cari kelas atau pengguna..."
                    emptyTitle="Belum ada ulasan"
                    emptyDescription="Ulasan pengguna akan tampil di sini."
                    dataLabel="ulasan"
                />
            </div>
        </AdminLayout>
    );
}
