import DataTable from '@/Components/Admin/DataTable';
import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-course-index.css';

const actionClass = 'course-index-action';

export default function Index() {
    const [status, setStatus] = useState('');
    const [type, setType] = useState('');
    const [refreshKey, setRefreshKey] = useState(0);

    const deleteCourse = (slug: string, title: string) => confirmDelete(route('admin.course.destroy', slug), {
        message: `Kelas “${title}” akan dihapus.`,
        onSuccess: () => setRefreshKey((key) => key + 1),
    });
    const columns = useMemo(() => [
        {
            accessorKey: 'title',
            header: 'Kelas',
            cell: ({ row, getValue }) => (
                <div className="course-index-name">
                    <img
                        src={row.original.thumbnail || '/assets/media/images/static/default-class.jpg'}
                        alt=""
                        loading="lazy"
                        className="course-index-thumb"
                        onError={(event) => {
                            event.currentTarget.onerror = null;
                            event.currentTarget.src = '/assets/media/images/static/default-class.jpg';
                        }}
                    />
                    <div className="min-w-0">
                        <Link href={route('admin.course.show', row.original.slug)} className="course-index-title-link">
                            {getValue() || 'Tanpa judul'}
                        </Link>
                        {row.original.category && <p className="course-index-category">{row.original.category}</p>}
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'type',
            header: 'Tipe kelas',
            cell: ({ getValue }) => {
                const scheduled = ['schedule', 'scheduled', 'terjadwal'].includes(String(getValue()).toLowerCase());
                return <span className="course-index-type"><i className={`bx ${scheduled ? 'bx-calendar' : 'bx-time-five'}`} aria-hidden />{scheduled ? 'Terjadwal' : 'Mandiri'}</span>;
            },
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ getValue }) => {
                const value = String(getValue() || '');
                return <span className={`course-index-status ${value === 'publish' ? 'is-published' : ''}`}><span aria-hidden />{value === 'publish' ? 'Aktif' : value === 'draft' ? 'Draft' : value || '—'}</span>;
            },
        },
        {
            accessorKey: 'opd_name',
            header: 'Cakupan OPD',
            cell: ({ row, getValue }) => <span className="course-index-opd">{getValue() || (row.original.audience_scope === 'all' ? 'Semua OPD' : '—')}</span>,
        },
        { accessorKey: 'visibility', header: 'Visibilitas', cell: ({ getValue }) => getValue() === 'public' ? 'Publik' : 'Privat' },
        { accessorKey: 'employee_status', header: 'Status pegawai' },
        { accessorKey: 'updated_at', header: 'Diperbarui' },
        { accessorKey: 'modifier_name', header: 'Terakhir diubah oleh', cell: ({ getValue }) => getValue() || '—' },
        {
            id: 'aksi',
            accessorKey: 'slug',
            header: 'Aksi',
            enableSorting: false,
            enableGlobalFilter: false,
            meta: { align: 'right' },
            cell: ({ row, getValue }) => (
                <div className="flex items-center justify-end gap-1">
                    {row.original.type === 'schedule' && <Link href={route('admin.course.schedules.index', getValue())} className={`${actionClass} is-schedule`} aria-label={`Kelola jadwal ${row.original.title}`} title="Kelola jadwal"><i className="bx bx-calendar" aria-hidden /><span className="course-index-action-label">Jadwal</span></Link>}
                    <Link href={route('admin.course.activities.index', getValue())} className={`${actionClass} is-activity`} aria-label={`Kelola kegiatan ${row.original.title}`} title="Kelola kegiatan"><i className="bx bx-list-check" aria-hidden /><span className="course-index-action-label">Kegiatan</span></Link>
                    <Link href={route('admin.course.edit', getValue())} className={`${actionClass} is-edit`} aria-label={`Edit kelas ${row.original.title}`} title="Edit"><i className="bx bx-edit-alt" aria-hidden /><span className="course-index-action-label">Edit</span></Link>
                    <button type="button" className={`${actionClass} is-delete`} aria-label={`Hapus kelas ${row.original.title}`} title="Hapus" onClick={() => deleteCourse(getValue(), row.original.title)}><i className="bx bx-trash" aria-hidden /><span className="course-index-action-label">Hapus</span></button>
                </div>
            ),
        },
    ], []);
    const hasFilters = Boolean(status || type);

    return (
        <AdminLayout title="Kelas" hideTitle>
            <Head title="Kelas" />
            <div className="course-index">
                <CrudPageHeader title="Kelas" description="Kelola kelas, jadwal, dan cakupan pengguna." className="course-index-header" actions={
                    <Link href={route('admin.course.create')} className="course-index-create"><i className="bx bx-plus" aria-hidden />Tambah kelas</Link>
                } />
                <DataTable
                    url={route('admin.course.index')}
                    columns={columns}
                    params={{ course_status: status, course_type: type }}
                    refreshKey={refreshKey}
                    dataLabel="kelas"
                    className="course-index-table admin-crud-table"
                    searchPlaceholder="Cari judul kelas..."
                    emptyTitle={hasFilters ? 'Tidak ada kelas yang sesuai' : 'Belum ada kelas'}
                    emptyDescription={hasFilters ? 'Ubah filter status atau tipe untuk melihat kelas lainnya.' : 'Buat kelas pertama untuk mulai menyusun pembelajaran.'}
                    toolbarFilters={
                        <div className="course-index-filters">
                            <label><span className="sr-only">Filter status</span><select value={status} onChange={(event) => setStatus(event.target.value)}><option value="">Semua status</option><option value="publish">Aktif</option><option value="draft">Draft</option></select></label>
                            <label><span className="sr-only">Filter tipe kelas</span><select value={type} onChange={(event) => setType(event.target.value)}><option value="">Semua tipe</option><option value="schedule">Terjadwal</option><option value="unschedule">Mandiri</option></select></label>
                            {hasFilters && <button type="button" className="course-index-reset" onClick={() => { setStatus(''); setType(''); }}>Reset filter</button>}
                        </div>
                    }
                />
            </div>
        </AdminLayout>
    );
}
