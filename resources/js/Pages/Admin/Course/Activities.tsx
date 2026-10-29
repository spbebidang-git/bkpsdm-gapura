import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { flexRender, getCoreRowModel, getFilteredRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';
import '@/../css/admin-course-index.css';
import '@/../css/admin-course-activities.css';

const activityTypes = {
    'pre-test': { label: 'Pre-Test / Kuis', icon: 'bx-list-check' },
    'post-test': { label: 'Post-Test', icon: 'bx-check-square' },
    meeting: { label: 'Pertemuan', icon: 'bx-calendar' },
    task: { label: 'Tugas', icon: 'bx-task' },
    video: { label: 'Video', icon: 'bx-video' },
    literature: { label: 'Literatur', icon: 'bx-book-open' },
};

const formatUpdatedAt = (value?: string | null) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};

function ActivityAction({ href, onClick, icon, label, title, variant = 'is-activity' }: {
    href?: string; onClick?: () => void; icon: string; label: string; title: string; variant?: string;
}) {
    const content = <><i className={`bx ${icon}`} aria-hidden /><span className="course-index-action-label">{label}</span></>;
    const props = { className: `course-index-action ${variant}`, 'aria-label': `${label}: ${title}`, title: label };
    return href ? <Link href={href} {...props}>{content}</Link> : <button type="button" onClick={onClick} {...props}>{content}</button>;
}

export default function Activities({ course, activities = [], canUnlock = false }) {
    const [dataActivities, setDataActivities] = useState(activities);
    const [globalFilter, setGlobalFilter] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [sorting, setSorting] = useState<any[]>([]);
    useEffect(() => setDataActivities(activities), [activities]);

    useEffect(() => {
        const timer = setTimeout(() => setGlobalFilter(searchInput.trim()), 300);
        return () => clearTimeout(timer);
    }, [searchInput]);

    const handleEndMeeting = (activityId) => {
        Swal.fire({
            title: 'Akhiri pertemuan?', text: 'Pertemuan ini akan diakhiri secara permanen.', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#018083', cancelButtonColor: '#8496B0',
            confirmButtonText: 'Ya, akhiri', cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;
            router.post(route('admin.course.activity.meeting.end-meeting', [course.slug, activityId]), {}, {
                preserveScroll: true,
                onSuccess: () => setDataActivities((prev) => prev.map((item) => item.id === activityId ? { ...item, end_meeting: 1 } : item)),
            });
        });
    };
    const handleLock = () => {
        Swal.fire({
            title: 'Kunci kegiatan?', text: 'Setelah dikunci, detail pelatihan dan kegiatan tidak bisa diubah lagi.', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#018083', cancelButtonColor: '#8496B0',
            confirmButtonText: 'Ya, kunci', cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) router.post(route('admin.course.lock', course.slug), {}, { preserveScroll: true });
        });
    };
    const handleUnlock = () => {
        Swal.fire({
            title: 'Buka kunci kegiatan?', text: 'Detail pelatihan dan kegiatan dapat diubah kembali setelah kunci dibuka.', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#018083', cancelButtonColor: '#8496B0',
            confirmButtonText: 'Ya, buka kunci', cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) router.post(route('admin.course.unlock', course.slug), {}, { preserveScroll: true });
        });
    };

    const createHref = route('admin.course.activity.activity', course.slug);

    const columns = useMemo(() => [
        {
            id: 'no',
            header: 'No',
            enableGlobalFilter: false,
            enableSorting: false,
            cell: ({ row }) => <span className="course-activities-number">{row.index + 1}</span>,
        },
        {
            id: 'type',
            header: 'Tipe',
            accessorFn: (row: any) => (activityTypes[row.type]?.label ?? row.type ?? ''),
            cell: ({ row }) => {
                const type = activityTypes[row.original.type] || { label: row.original.type || '—', icon: 'bx-detail' };
                return <span className="course-activities-type"><i className={`bx ${type.icon}`} aria-hidden />{type.label}</span>;
            },
        },
        {
            accessorKey: 'title',
            header: 'Judul',
            cell: ({ getValue }) => <span className="course-activities-title">{getValue() || 'Tanpa judul'}</span>,
        },
        {
            accessorKey: 'value',
            header: 'Nilai',
            meta: { align: 'center' },
            cell: ({ getValue }) => <span className="tabular-nums">{getValue() ?? '—'}</span>,
        },
        {
            accessorKey: 'updated_at',
            header: 'Terakhir diubah',
            cell: ({ getValue }) => <span className="course-activities-updated">{formatUpdatedAt(getValue() as string)}</span>,
        },
        {
            accessorKey: 'modifier_name',
            header: 'Terakhir diubah oleh',
            cell: ({ getValue }) => <span>{getValue() || '—'}</span>,
        },
        {
            id: 'aksi',
            header: 'Aksi',
            enableGlobalFilter: false,
            enableSorting: false,
            meta: { align: 'right' },
            cell: ({ row }) => {
                const item = row.original;
                const assessment = item.type === 'pre-test' || item.type === 'post-test';
                return <div className="course-activities-row-actions">
                    {!course.is_locked && assessment && !item.has_imported && <ActivityAction href={route('admin.course.activity.import-question.index', { course: course.slug, activity: item.id })} icon="bx-import" label="Import soal" title={item.title} />}
                    {!course.is_locked && assessment && <ActivityAction href={`/admin/course/${course.slug}/activity/variant/${item.id}`} icon="bx-layer" label="Variasi" title={item.title} />}
                    {!course.is_locked && item.type === 'meeting' && item.end_meeting === 0 && <ActivityAction onClick={() => handleEndMeeting(item.id)} icon="bx-stop-circle" label="Akhiri sesi" title={item.title} variant="is-schedule" />}
                    {item.type === 'task' && <ActivityAction href={route('admin.course.activity.result', { course: course.slug, activityId: item.id })} icon="bx-bar-chart-alt-2" label="Hasil" title={item.title} />}
                    {!course.is_locked && <>
                        <ActivityAction href={`/admin/course/${course.slug}/activity/${item.type}/${item.id}/edit`} icon="bx-edit-alt" label="Edit" title={item.title} variant="is-edit" />
                        <ActivityAction onClick={() => confirmDelete(`/admin/course/${course.slug}/activity/${item.id}`, { message: `Aktivitas “${item.title}” akan dihapus.` })} icon="bx-trash" label="Hapus" title={item.title} variant="is-delete" />
                    </>}
                    {course.is_locked && item.type !== 'task' && <span className="course-activities-no-action" aria-label="Kegiatan terkunci">—</span>}
                </div>;
            },
        },
    ], [course.slug, course.is_locked]);

    const table = useReactTable({
        data: dataActivities,
        columns,
        state: { sorting, globalFilter },
        onSortingChange: setSorting,
        onGlobalFilterChange: setGlobalFilter,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
    });

    const filteredCount = table.getFilteredRowModel().rows.length;
    const sortIcon = (columnId: string) => {
        const sort = sorting.find((item) => item.id === columnId);
        if (!sort) return 'bx-sort-alt-2';
        return sort.desc ? 'bx-sort-down' : 'bx-sort-up';
    };

    return <AdminLayout title="Kelola kegiatan" hideTitle>
        <Head title={`Kegiatan · ${course.title}`} />
        <div className="course-index course-activities-page">
            <CrudPageHeader title={course.title} description="Kelola aktivitas pembelajaran dan evaluasi kelas." className="course-index-header" actions={<>
                <Link href={route('admin.course.index')} className="course-activities-secondary"><i className="bx bx-left-arrow-alt" aria-hidden />Kembali</Link>
                <Link href={route('admin.course.edit', course.slug)} className="course-activities-secondary"><i className="bx bx-edit-alt" aria-hidden />Edit kelas</Link>
                {!course.is_locked && <Link href={createHref} className="course-index-create"><i className="bx bx-plus" aria-hidden />Tambah kegiatan</Link>}
            </>} />
            <section className="admin-table-frame course-index-table admin-crud-table course-activities-table" aria-labelledby="activity-list-title">
                <div className="admin-table-toolbar course-activities-toolbar">
                    <label className="relative min-w-0 flex-1 sm:max-w-sm">
                        <span className="sr-only">Cari aktivitas</span>
                        <i className="bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-stone" aria-hidden />
                        <input
                            type="search"
                            value={searchInput}
                            onChange={(event) => setSearchInput(event.target.value)}
                            placeholder="Cari tipe atau judul aktivitas..."
                            className="admin-search-input"
                        />
                        {searchInput && (
                            <button type="button" onClick={() => setSearchInput('')} className="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-steel hover:bg-surface hover:text-ink" aria-label="Hapus pencarian">
                                <i className="bx bx-x text-lg" aria-hidden />
                            </button>
                        )}
                    </label>
                    <div className="course-activities-toolbar-actions">
                        <span className="course-activities-count">{filteredCount.toLocaleString('id-ID')} aktivitas</span>
                        {course.is_locked
                            ? <><span className="course-activities-locked"><i className="bx bx-lock-alt" aria-hidden />Kegiatan terkunci</span>
                                {canUnlock && <button type="button" onClick={handleUnlock} className="course-activities-secondary"><i className="bx bx-lock-open-alt" aria-hidden />Buka kunci kegiatan</button>}</>
                            : <button type="button" onClick={handleLock} className="course-activities-secondary"><i className="bx bx-lock-alt" aria-hidden />Kunci kegiatan</button>}
                    </div>
                </div>
                <div className="admin-table-scroll">
                    <table className="admin-table w-full text-sm">
                        <thead>{table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id}>{headerGroup.headers.map((header) => {
                                const canSort = header.column.getCanSort();
                                const alignment = (header.column.columnDef.meta as any)?.align ?? 'left';
                                return <th key={header.id} scope="col"
                                    aria-sort={header.column.getIsSorted() === 'asc' ? 'ascending' : header.column.getIsSorted() === 'desc' ? 'descending' : undefined}
                                    className={alignment === 'center' ? 'text-center' : alignment === 'right' ? 'text-right' : 'text-left'}
                                >
                                    {canSort ? (
                                        <button type="button" onClick={header.column.getToggleSortingHandler()}
                                            className={`inline-flex items-center gap-1.5 rounded-sm text-inherit hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${alignment === 'right' ? 'justify-end' : alignment === 'center' ? 'justify-center' : 'justify-start'}`}>
                                            {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                            <i className={`bx ${sortIcon(header.column.id)} text-base ${sorting.some((item) => item.id === header.column.id) ? 'text-primary opacity-100' : 'opacity-35'}`} aria-hidden />
                                        </button>
                                    ) : header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                </th>;
                            })}</tr>
                        ))}</thead>
                        <tbody>
                            {table.getRowModel().rows.map((row) => (
                                <tr key={row.id}>{row.getVisibleCells().map((cell) => {
                                    const alignment = (cell.column.columnDef.meta as any)?.align ?? 'left';
                                    return <td key={cell.id} className={alignment === 'center' ? 'text-center' : alignment === 'right' ? 'text-right' : 'text-left'}>
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </td>;
                                })}</tr>
                            ))}
                            {filteredCount === 0 && <tr>
                                <td colSpan={columns.length} className="admin-table-state">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-mist text-primary">
                                        <i className={`bx ${globalFilter ? 'bx-search' : 'bx-list-check'} text-xl`} aria-hidden />
                                    </span>
                                    <p className="font-semibold text-ink">{globalFilter ? 'Tidak ada hasil' : 'Belum ada aktivitas'}</p>
                                    <p className="max-w-sm text-sm text-steel">
                                        {globalFilter
                                            ? `Tidak ada aktivitas yang cocok dengan “${globalFilter}”.`
                                            : course.is_locked ? 'Kelas ini belum memiliki aktivitas dan kegiatan sudah terkunci.' : 'Tambahkan materi, pertemuan, tugas, atau evaluasi untuk menyusun pembelajaran kelas.'}
                                    </p>
                                    {!globalFilter && !course.is_locked && <Link href={createHref} className="course-index-create mt-2"><i className="bx bx-plus" aria-hidden />Tambah kegiatan</Link>}
                                </td>
                            </tr>}
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>;
}
