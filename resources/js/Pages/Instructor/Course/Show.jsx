import {
    badgeOk,
    badgePending,
    badgeWarn,
    btnDanger,
    btnLink,
    btnPrimarySm,
    btnSecondarySm,
    btnXs,
    card,
    pageDescription,
    pageHeader,
    pageTitle,
    tableClass,
    tableScroller,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Swal from 'sweetalert2';

function typeLabel(type) {
    if (type === 'pre-test') return 'Pre-Test / Kuis';
    if (type === 'post-test') return 'Post-Test';
    if (type === 'meeting') return 'Pertemuan';
    if (type === 'task') return 'Tugas';
    if (type === 'video') return 'Video';
    if (type === 'literature') return 'Literatur';
    return type || '—';
}

const actionBtn = btnXs;

export default function Show({ course, activities = [], trainers = [] }) {
    const [dataActivities, setDataActivities] = useState(activities);

    useEffect(() => {
        setDataActivities(activities);
    }, [activities]);

    const handleEndMeeting = (e, activityId) => {
        e.preventDefault();
        Swal.fire({
            title: 'Akhiri pertemuan?',
            text: 'Pertemuan ini akan diakhiri secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003C52',
            cancelButtonColor: '#8496B0',
            confirmButtonText: 'Ya, akhiri',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;
            router.post(
                route('admin.course.activity.meeting.end-meeting', [course.slug, activityId])
            );
            setDataActivities((prev) =>
                prev.map((a) => (a.id === activityId ? { ...a, end_meeting: 1 } : a))
            );
        });
    };

    const handleSubmitDelete = (e, activityId) => {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus aktivitas?',
            text: 'Aktivitas ini akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e03131',
            cancelButtonColor: '#8496B0',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;
            router.delete(`/admin/course/${course.slug}/activity/${activityId}`, {
                onSuccess: () => {
                    router.get(`/admin/course/${course.slug}`);
                    Swal.fire('Berhasil', 'Aktivitas berhasil dihapus.', 'success');
                },
            });
        });
    };

    const handleLock = () => {
        Swal.fire({
            title: 'Kunci kegiatan?',
            text: 'Setelah dikunci, detail pelatihan dan kegiatan tidak bisa diubah lagi.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e03131',
            cancelButtonColor: '#8496B0',
            confirmButtonText: 'Ya, kunci',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;
            router.post(route('admin.course.lock', course.slug), {}, { preserveScroll: true });
        });
    };

    return (
        <AdminLayout title="Kelola Kelas" hideTitle>
            <Head title={course.title} />

            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className="mb-1 text-sm font-medium text-primary-pressed">Kelas / Kelola</p>
                        <h1 className={pageTitle}>{course.title}</h1>
                        {course.short_description && (
                            <p className={pageDescription}>
                                {course.short_description}
                            </p>
                        )}
                    </div>
                    <div className="flex shrink-0 flex-wrap items-center gap-2">
                        {course.slug && (
                            <Link
                                href={route('admin.course.overview', course.slug)}
                                className={btnSecondarySm}
                            >
                                Ringkasan
                            </Link>
                        )}
                        <Link href={route('admin.course.index')} className={btnSecondarySm}>
                            <i className="bx bx-left-arrow-alt text-base" aria-hidden />
                            Kembali
                        </Link>
                    </div>
                </div>

                <section className={`${card} p-4 sm:p-5`} aria-label="Ringkasan kelas">
                    <div className="flex flex-col gap-5 md:flex-row md:items-center">
                        {course.thumbnail && (
                            <img
                                src={course.thumbnail}
                                alt=""
                                className="h-32 w-full rounded-lg border border-hairline object-cover md:h-24 md:w-40"
                            />
                        )}
                        <div className="grid flex-1 grid-cols-2 divide-x divide-y divide-hairline overflow-hidden rounded-lg border border-hairline sm:grid-cols-4 sm:divide-y-0">
                            <div className="bg-canvas px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-steel">
                                    Peserta
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums text-ink">
                                    {course.total_participant ?? 0}
                                </p>
                            </div>
                            <div className="bg-canvas px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-steel">
                                    Lulus
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums text-ink">
                                    {course.total_passed ?? 0}
                                </p>
                            </div>
                            <div className="bg-canvas px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-steel">
                                    Efektivitas
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums text-ink">
                                    {course.efektifitas ?? 0}
                                    <span className="text-sm font-medium text-steel">%</span>
                                </p>
                            </div>
                            <div className="bg-canvas px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-steel">
                                    Status
                                </p>
                                <p className="mt-1.5">
                                    {course.is_locked ? (
                                        <span className={badgePending}>Terkunci</span>
                                    ) : (
                                        <span className={badgeOk}>Terbuka</span>
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className={`${card} p-4 sm:p-5`} aria-labelledby="trainer-list-title">
                    <h2 id="trainer-list-title" className="text-base font-semibold text-ink">Trainer</h2>
                    <p className="mt-0.5 mb-3 text-xs text-steel">Trainer yang mengampu kelas ini.</p>
                    {trainers.length ? (
                        <ul className="flex flex-wrap gap-3">
                            {trainers.map((trainer) => (
                                <li key={trainer.id} className="flex items-center gap-3 rounded-lg border border-hairline bg-canvas px-3 py-2">
                                    <img
                                        src={trainer.photo_src}
                                        alt={trainer.name}
                                        className="h-9 w-9 rounded-full border border-hairline object-cover"
                                    />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-ink">{trainer.name}</p>
                                        <p className="truncate text-xs text-steel">{trainer.jabatan || '—'}</p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-steel">Belum ada trainer yang ditetapkan.</p>
                    )}
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="activity-list-title">
                    <div className="flex flex-col gap-3 border-b border-hairline px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-wrap items-center gap-2">
                            <div>
                                <h2 id="activity-list-title" className="text-base font-semibold text-ink">Daftar aktivitas</h2>
                                <p className="mt-0.5 text-xs text-steel">{dataActivities.length} aktivitas pembelajaran</p>
                            </div>
                            {!course.is_locked ? (
                                <button type="button" onClick={handleLock} className={btnDanger}>
                                    <i className="bx bx-lock-alt text-sm" />
                                    Kunci kegiatan
                                </button>
                            ) : (
                                <span className={badgePending}>
                                    <i className="bx bx-lock text-sm" />
                                    Sudah terkunci
                                </span>
                            )}
                        </div>
                        {!course.is_locked && (
                            <Link
                                href={`/admin/course/${course.slug}/activity`}
                                className={`${btnPrimarySm} shrink-0`}
                            >
                                <i className="bx bx-plus text-base" />
                                Tambah aktivitas
                            </Link>
                        )}
                    </div>

                    <div className={tableScroller}>
                        <table className={`${tableClass} min-w-[760px]`}>
                            <thead>
                                <tr>
                                    <th className="text-left">No</th>
                                    <th className="text-left">Tipe</th>
                                    <th className="admin-sticky-cell text-left">Judul</th>
                                    <th className="text-center">Nilai</th>
                                    <th className="text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {dataActivities.length > 0 ? (
                                    dataActivities.map((item, index) => (
                                        <tr key={item.id ?? index}>
                                            <td className="tabular-nums text-steel">
                                                {index + 1}
                                            </td>
                                            <td>
                                                <span className="text-steel">{typeLabel(item.type)}</span>
                                            </td>
                                            <td className="admin-sticky-cell max-w-xs font-medium text-primary-pressed">
                                                <span className="line-clamp-2">{item.title}</span>
                                            </td>
                                            <td className="text-center tabular-nums text-steel">
                                                {item.value || '—'}
                                            </td>
                                            <td>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    {(item.type === 'pre-test' ||
                                                        item.type === 'post-test') && (
                                                        <>
                                                            {!course.is_locked &&
                                                                !item.has_imported && (
                                                                    <Link
                                                                        href={route(
                                                                            'admin.course.activity.import-question.index',
                                                                            {
                                                                                course: course.slug,
                                                                                activity: item.id,
                                                                            }
                                                                        )}
                                                                        className={btnXs}
                                                                    >
                                                                        Import
                                                                    </Link>
                                                                )}
                                                            {!course.is_locked && (
                                                                <Link
                                                                    href={`/admin/course/${course.slug}/activity/variant/${item.id}`}
                                                                    className={actionBtn}
                                                                >
                                                                    Variasi
                                                                </Link>
                                                            )}
                                                        </>
                                                    )}

                                                    {!course.is_locked &&
                                                        item.type === 'meeting' &&
                                                        item.end_meeting === 0 && (
                                                            <button
                                                                type="button"
                                                                onClick={(e) =>
                                                                    handleEndMeeting(e, item.id)
                                                                }
                                                                className={actionBtn}
                                                            >
                                                                Akhiri sesi
                                                            </button>
                                                        )}

                                                    {item.type === 'task' && (
                                                        <Link
                                                            href={route(
                                                                'admin.course.activity.result',
                                                                {
                                                                    course: course.slug,
                                                                    activityId: item.id,
                                                                }
                                                            )}
                                                            className={actionBtn}
                                                        >
                                                            Hasil
                                                        </Link>
                                                    )}

                                                    {!course.is_locked && (
                                                        <Link
                                                            href={`/admin/course/${course.slug}/activity/${item.type}/${item.id}/edit`}
                                                            className={btnXs}
                                                        >
                                                            Edit
                                                        </Link>
                                                    )}

                                                    {!course.is_locked && (
                                                        <button
                                                            type="button"
                                                            onClick={(e) =>
                                                                handleSubmitDelete(e, item.id)
                                                            }
                                                            className={btnDanger}
                                                        >
                                                            Hapus
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-10 text-center text-stone"
                                        >
                                            Belum ada aktivitas.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}
