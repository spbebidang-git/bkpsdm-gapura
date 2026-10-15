import DataTable from '@/Components/Admin/DataTable';
import { RowActions } from '@/Components/Admin/ActionButtons';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-assessment.css';

export default function Index({ slug, activityId, questionId, activity }) {
    const base = `/admin/course/${slug}/activity/variant/${activityId}/${questionId}/question-item`;
    const [refreshKey, setRefreshKey] = useState(0);
    const columns = useMemo(() => [

        { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => table.getState().pagination.pageIndex * table.getState().pagination.pageSize + row.index + 1 },
        { accessorKey: 'question', header: 'Pertanyaan', cell: ({ getValue }) => <span className="assessment-name assessment-question">{getValue()}</span> },
        ...[1, 2, 3, 4].map((number) => ({
            accessorKey: `option${number}`,
            header: `Jawaban ${String.fromCharCode(64 + number)}`,
            enableSorting: false,
            cell: ({ row, getValue }) => <span className={`assessment-answer ${String(row.original.correct_answer) === String(number) ? 'text-primary font-bold' : ''}`}>{getValue()}</span>,
        })),
        { accessorKey: 'score', header: 'Skor', meta: { align: 'right' }, enableGlobalFilter: false, cell: ({ getValue }) => <span className="block text-center">{getValue()}</span> },
        { accessorKey: 'updated_at', header: 'Terakhir diperbarui' },
        { id: 'aksi', accessorKey: 'id', header: 'Aksi', enableSorting: false, enableGlobalFilter: false, meta: { align: 'right' }, cell: ({ row }) => <RowActions editHref={`${base}/${row.original.id}/edit`} deleteUrl={`${base}/${row.original.id}`} editLabel="Ubah soal" deleteMessage={`Soal “${row.original.question}” akan dihapus dari varian ini. Tindakan ini tidak dapat dibatalkan.`} onDeleted={() => setRefreshKey((key) => key + 1)} iconOnly variant="course" /> },
    ], [base]);
    return <AdminLayout title="Soal varian" hideTitle>
        <Head title="Soal varian" />
        <div className="assessment-admin assessment-question-page course-index">
            <CrudPageHeader title="Soal varian" description={`${activity.title} · Varian ${activity.variant}. Kelola pertanyaan dan kunci jawaban.`} actions={<>
                <Link href={`${base}/create`} className="course-index-create"><i className="bx bx-plus" aria-hidden />Tambah soal</Link>
                <Button variant="secondary" href={route('admin.course.activity.variant.index', [slug, activityId])}><i className="bx bx-arrow-back" aria-hidden />Kembali</Button>
            </>} />
            <dl className="question-variant-summary" aria-label="Informasi varian soal">
                <div><dt>Jenis tes</dt><dd>{activity.type}</dd></div>
                <div><dt>Varian</dt><dd>{activity.variant}</dd></div>
                <div><dt>Acak soal</dt><dd>{activity.mix ? 'Ya' : 'Tidak'}</dd></div>
                <div><dt>Ulangi tes</dt><dd>{activity.is_loop ? 'Ya' : 'Tidak'}</dd></div>
            </dl>
            <DataTable url={base} columns={columns} refreshKey={refreshKey} className="course-index-table admin-crud-table assessment-question-table" searchPlaceholder="Cari pertanyaan atau pilihan jawaban..." emptyTitle="Belum ada soal" emptyDescription="Tambahkan pertanyaan pertama untuk varian ini." dataLabel="soal" />
        </div>
    </AdminLayout>;
}
