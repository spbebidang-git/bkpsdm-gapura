import DataTable from '@/Components/Admin/DataTable';
import { RowActions } from '@/Components/Admin/ActionButtons';
import { CrudPageHeader, CrudStatusBadge } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-assessment.css';

export default function Index({ slug, activity, variants = [], variantWarning, canImport }: {
    slug: string;
    activity: { activity_id: string | number; title: string; description?: string | null; type?: string };
    variants?: { question_count: string | number }[];
    variantWarning?: { hasEmpty: boolean; hasMismatch: boolean; emptyVariants: number[] } | null;
    canImport?: boolean;
}) {
    const base = `/admin/course/${slug}/activity/variant/${activity.activity_id}`;
    const [refreshKey, setRefreshKey] = useState(0);
    const totalQuestions = useMemo(() => variants.reduce((sum, item) => sum + (Number(item.question_count) || 0), 0), [variants]);
    const questionsPerVariant = variants.length ? new Set(variants.map((item) => Number(item.question_count) || 0)) : null;
    const even = questionsPerVariant ? questionsPerVariant.size === 1 : true;
    const ready = variants.length > 0 && even && (questionsPerVariant?.size !== 1 || !questionsPerVariant.has(0));
    const columns = useMemo(() => [

        { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => table.getState().pagination.pageIndex * table.getState().pagination.pageSize + row.index + 1 },
        { accessorKey: 'variant', header: 'Varian', cell: ({ getValue }) => <span className="assessment-name">Varian {getValue()}</span> },
        { accessorKey: 'code', header: 'Kode', cell: ({ getValue }) => <span className="assessment-code">{getValue()}</span> },
        { accessorKey: 'question_count', header: 'Jumlah soal', meta: { align: 'right' }, enableGlobalFilter: false },
        { accessorKey: 'mix', header: 'Acak soal', enableGlobalFilter: false, cell: ({ getValue }) => <CrudStatusBadge active={Boolean(getValue())} activeLabel="Ya" inactiveLabel="Tidak" /> },
        { accessorKey: 'updated_at', header: 'Terakhir diperbarui' },
        { id: 'aksi', accessorKey: 'id', header: 'Aksi', enableSorting: false, enableGlobalFilter: false, meta: { align: 'right' }, cell: ({ row }) => <div className="assessment-row-actions">
            <Link href={`${base}/${row.original.id}/question-item`} title="Atur soal" aria-label={`Atur soal varian ${row.original.variant}`} className="assessment-question-action"><i className="bx bx-list-ul" aria-hidden /></Link>
            <RowActions editHref={`${base}/${row.original.id}/edit`} deleteUrl={`${base}/${row.original.id}`} deleteMessage={`Varian ${row.original.variant} (${row.original.code}) beserta paket soalnya akan dihapus.`} onDeleted={() => setRefreshKey((key) => key + 1)} iconOnly variant="course" />
        </div> },
    ], [base]);
    return <AdminLayout title="Varian soal" hideTitle>
        <Head title="Varian soal" />
        <div className="assessment-admin course-index variant-index">
            <CrudPageHeader title="Varian soal" className="course-index-header" description={`Kelola paket soal untuk ${activity.title}.`} actions={<>
                <Link href={`${base}/create`} className="course-index-create"><i className="bx bx-plus" aria-hidden />Tambah varian</Link>
                {canImport && <Button variant="secondary" href={route('admin.course.activity.import-question.index', [slug, activity.activity_id])}><i className="bx bx-import" aria-hidden />Impor soal</Button>}
                <Button variant="secondary" href={route('admin.course.activities.index', slug)}><i className="bx bx-arrow-back" aria-hidden />Kembali</Button>
            </>} />

            <section className="variant-index__summary" aria-label="Ringkasan kegiatan">
                <div className="variant-index__summary-copy">
                    <h2>{activity.title}</h2>
                    {activity.description && <p>{activity.description}</p>}
                </div>
                <div className="variant-index__summary-meta">
                    <span>{variants.length.toLocaleString('id-ID')} varian</span>
                    <span>{totalQuestions.toLocaleString('id-ID')} soal</span>
                    <span className={ready ? 'is-ready' : ''}>{ready ? 'Siap digunakan' : variants.length === 0 ? 'Belum ada varian' : totalQuestions === 0 ? 'Belum ada soal' : !even ? 'Perlu disamakan' : 'Perlu dilengkapi'}</span>
                </div>
            </section>

            {variantWarning && <div className="assessment-notice" role="alert"><i className="bx bx-error-circle" aria-hidden /><div>
                {variantWarning.hasEmpty && <p>Varian {variantWarning.emptyVariants.join(', ')} belum memiliki soal dan akan dilewati saat tes.</p>}
                {variantWarning.hasMismatch && <p>Jumlah soal antarvarian belum sama. Samakan jumlahnya agar seluruh paket dapat digunakan.</p>}
            </div></div>}
            <DataTable url={base} columns={columns} refreshKey={refreshKey} className="course-index-table admin-crud-table" searchPlaceholder="Cari kode atau nomor varian..." emptyTitle="Belum ada varian" emptyDescription="Tambahkan varian atau impor paket soal dari template Excel." dataLabel="varian" />
        </div>
    </AdminLayout>;
}
