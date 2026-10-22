import { badgeOk, badgePending, btnLink, btnPrimarySm, card, tableClass, tableScroller } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';

export default function Show({
    course,
    schedules = [],
    finishedParticipant = [],
    onGoingParticipant = [],
    canResetLearning = false,
}) {
    const resetLearning = (participant) => {
        Swal.fire({
            title: 'Reset proses belajar peserta?',
            text: `Progres, jawaban, nilai, tugas, dan sertifikat ${participant.name} pada kelas ini akan diarsipkan. Peserta perlu aktivasi ulang dan memulai dari awal. Sertifikat yang sudah tercatat di KEDATON/Prestige tidak dapat ditarik otomatis.`,
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonColor: '#A43B2F',
            cancelButtonColor: '#52615D',
            confirmButtonText: 'Ya, reset belajar',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) router.post(route('admin.course.participant.reset-learning', [course.slug, participant.id]), {}, { preserveScroll: true });
        });
    };
    return (
        <AdminLayout
            title="Detail Kelas"
            actions={
                <div className="flex items-center gap-2">
                    {course.slug && (
                        <Link href={route('admin.course.show', course.slug)} className={btnPrimarySm}>
                            Kelola materi
                        </Link>
                    )}
                    <Link href={route('admin.course.index')} className={btnLink}>
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title={course.title} />

            <div className={`${card} p-6 mb-6`}>
                <div className="flex flex-col md:flex-row gap-6">
                    {course.thumbnail && (
                        <img
                            src={course.thumbnail}
                            alt=""
                            className="w-full md:w-48 h-32 object-cover rounded-md border border-hairline"
                        />
                    )}
                    <div className="flex-1 space-y-2 text-sm">
                        <h2 className="text-xl font-semibold text-ink tracking-tight">{course.title}</h2>
                        <div className="grid sm:grid-cols-2 gap-2 text-charcoal">
                            <div>
                                <span className="text-steel">Kategori:</span> {course.category ?? '-'}
                            </div>
                            <div>
                                <span className="text-steel">Tipe:</span> {course.type ?? '-'}
                            </div>
                            <div>
                                <span className="text-steel">Status:</span>{' '}
                                <span className={course.status === 'publish' ? badgeOk : badgePending}>{course.status === 'publish' ? 'Aktif' : course.status ?? '—'}</span>
                            </div>
                            <div>
                                <span className="text-steel">JP:</span> {course.jam_pelajaran ?? '-'}
                            </div>
                            <div>
                                <span className="text-steel">Peserta / Selesai:</span>{' '}
                                {course.total_participant ?? 0} / {course.total_passed ?? 0}
                            </div>
                        </div>
                        <p className="text-steel">{course.visibility === 'public' ? 'Publik — semua OPD' : `Privat — ${course.opd_name || 'OPD tertentu'}`}</p>
                        <p className="text-steel">Status pegawai: {course.employee_status || '—'}</p>
                        {course.short_description && (
                            <p className="text-steel pt-2">{course.short_description}</p>
                        )}
                    </div>
                </div>
            </div>

            {schedules?.length > 0 && (
                <div className={`${card} mb-6 overflow-hidden`}>
                    <div className="px-5 py-3 border-b border-hairline font-semibold text-ink">
                        Jadwal
                    </div>
                    <div className={tableScroller}>
                    <table className={`${tableClass} min-w-[560px]`}>
                        <thead className="bg-surface text-steel">
                            <tr>
                                <th className="admin-sticky-cell px-4 py-2 text-left font-medium">Tanggal</th>
                                <th className="px-4 py-2 text-left font-medium">Waktu</th>
                                <th className="px-4 py-2 text-right font-medium">Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            {schedules.map((s) => (
                                <tr key={s.id} className="border-t border-hairline-soft">
                                    <td className="admin-sticky-cell px-4 py-2 text-charcoal">{s.date ?? '-'}</td>
                                    <td className="px-4 py-2 text-charcoal">
                                        {s.time_from ?? '-'} – {s.time_to ?? '-'}
                                    </td>
                                    <td className="px-4 py-2 text-right text-charcoal">
                                        {s.quota ?? '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    </div>
                </div>
            )}

            <div className="grid md:grid-cols-2 gap-6">
                <div className={`${card} overflow-hidden`}>
                    <div className="px-5 py-3 border-b border-hairline font-semibold text-ink">
                        Selesai ({finishedParticipant.length})
                    </div>
                    <ul className="max-h-64 overflow-y-auto text-sm divide-y divide-hairline-soft">
                        {finishedParticipant.map((p, i) => (
                            <li key={p.id ?? i} className="flex items-center justify-between gap-3 px-4 py-2 text-charcoal">
                                <span><span className="font-medium text-ink">{p.name}</span>{p.job_title ? <span className="text-steel text-xs ml-2">{p.job_title}</span> : null}</span>
                                {canResetLearning && p.id && <button type="button" onClick={() => resetLearning(p)} className="shrink-0 rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Reset belajar</button>}
                            </li>
                        ))}
                        {finishedParticipant.length === 0 && (
                            <li className="px-4 py-6 text-center text-stone">Belum ada peserta selesai</li>
                        )}
                    </ul>
                </div>
                <div className={`${card} overflow-hidden`}>
                    <div className="px-5 py-3 border-b border-hairline font-semibold text-ink">
                        Berjalan ({onGoingParticipant.length})
                    </div>
                    <ul className="max-h-64 overflow-y-auto text-sm divide-y divide-hairline-soft">
                        {onGoingParticipant.map((p, i) => (
                            <li key={p.id ?? i} className="flex items-center justify-between gap-3 px-4 py-2 text-charcoal">
                                <span>{p.name}</span>
                                {canResetLearning && p.id && <button type="button" onClick={() => resetLearning(p)} className="shrink-0 rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Reset belajar</button>}
                            </li>
                        ))}
                        {onGoingParticipant.length === 0 && (
                            <li className="px-4 py-6 text-center text-stone">Belum ada peserta aktif</li>
                        )}
                    </ul>
                </div>
            </div>
        </AdminLayout>
    );
}
