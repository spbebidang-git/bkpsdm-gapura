import { badgeOk, badgePending, badgeWarn, btnSecondarySm, btnWarnSm, card, pageDescription, pageTitle } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ course, trainers = [], schedules = [] }) {
    return (
        <AdminLayout title="Kelola Kelas" hideTitle>
            <Head title={course.title} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div className="min-w-0">
                        <h1 className={pageTitle}>{course.title}</h1>
                        {course.short_description && <p className={pageDescription}>{course.short_description}</p>}
                    </div>
                    <div className="flex flex-wrap items-center gap-2 xl:shrink-0">
                        <Link href={route('admin.course.activities.index', course.slug)} className={btnSecondarySm}><i className="bx bx-list-check text-base" aria-hidden />Kelola kegiatan</Link>
                        {course.type === 'schedule' && <Link href={route('admin.course.schedules.index', course.slug)} className={btnSecondarySm}><i className="bx bx-calendar text-base" aria-hidden />Kelola jadwal</Link>}
                        <Link href={route('admin.course.edit', course.slug)} className={btnWarnSm}>
                            <i className="bx bx-edit-alt text-base" aria-hidden />
                            Edit kelas
                        </Link>
                        {course.slug && (
                            <Link href={route('admin.course.overview', course.slug)} className={btnSecondarySm}>
                                Ringkasan
                            </Link>
                        )}
                        <Link href={route('admin.course.index')} className={btnSecondarySm}>
                            <i className="bx bx-left-arrow-alt text-base" aria-hidden />
                            Kembali
                        </Link>
                    </div>
                </div>

                <section className={`${card} overflow-hidden`} aria-labelledby="course-detail-title">
                    <div className={`grid ${course.thumbnail ? 'md:grid-cols-[minmax(220px,30%)_minmax(0,1fr)]' : ''}`}>
                        {course.thumbnail && (
                            <img
                                src={course.thumbnail}
                                alt=""
                                className="h-52 w-full object-cover md:h-full md:min-h-[250px]"
                            />
                        )}
                        <div className="p-5 sm:p-6">
                            <div className="flex flex-wrap gap-2">
                                <span className={course.status === 'publish' ? badgeOk : badgePending}>
                                    {course.status === 'publish' ? 'Aktif' : course.status ?? '—'}
                                </span>
                                <span className={course.is_locked ? badgePending : badgeOk}>
                                    {course.is_locked ? 'Kegiatan terkunci' : 'Kegiatan terbuka'}
                                </span>
                            </div>
                            <h2 id="course-detail-title" className="mt-5 text-base font-semibold text-ink">Informasi kelas</h2>
                            <dl className="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-xs text-steel">Kategori</dt>
                                    <dd className="mt-1 text-sm font-medium text-ink">{course.category ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-steel">Tipe kelas</dt>
                                    <dd className="mt-1 text-sm font-medium text-ink">{course.type === 'schedule' ? 'Terjadwal' : 'Tidak terjadwal'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-steel">Jam pelajaran</dt>
                                    <dd className="mt-1 text-sm font-medium tabular-nums text-ink">{course.jam_pelajaran ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-steel">Cakupan</dt>
                                    <dd className="mt-1 text-sm font-medium text-ink">{course.visibility === 'public' ? 'Publik — semua OPD' : `Privat — ${course.opd_name || 'OPD belum ditetapkan'}`}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-steel">Terakhir diubah oleh</dt>
                                    <dd className="mt-1 text-sm font-medium text-ink">{course.modifier_name || '—'}</dd>
                                </div>
                            </dl>
                            <div className="mt-5 border-t border-hairline pt-4">
                                <p className="text-xs text-steel">Target status kepegawaian</p>
                                <div className="mt-2 flex flex-wrap gap-1.5">
                                    {course.employee_status?.length ? course.employee_status.map((status) => (
                                        <span key={status.id} className={badgeWarn}>{status.title}</span>
                                    )) : <span className="text-sm text-steel">—</span>}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan kelas">
                    <div className={`${card} px-5 py-4 sm:px-6`}>
                        <p className="text-xs font-medium text-steel">Peserta</p>
                        <p className="mt-2 text-2xl font-semibold tabular-nums text-[#075D5E]">{course.total_participant ?? 0}</p>
                    </div>
                    <div className={`${card} px-5 py-4 sm:px-6`}>
                        <p className="text-xs font-medium text-steel">Lulus</p>
                        <p className="mt-2 text-2xl font-semibold tabular-nums text-[#075D5E]">{course.total_passed ?? 0}</p>
                    </div>
                    <div className={`${card} px-5 py-4 sm:px-6`}>
                        <p className="text-xs font-medium text-steel">Efektivitas</p>
                        <p className="mt-2 text-2xl font-semibold tabular-nums text-[#075D5E]">
                            {course.efektifitas ?? 0}<span className="ml-0.5 text-base text-steel">%</span>
                        </p>
                    </div>
                </section>

                <div className={`grid items-start gap-5 ${course.type === 'schedule' ? 'lg:grid-cols-[minmax(0,1.25fr)_minmax(280px,0.75fr)]' : ''}`}>
                    {course.type === 'schedule' && <section className={`${card} overflow-hidden`} aria-labelledby="schedule-title">
                        <div className="border-b border-hairline px-5 py-4 sm:px-6">
                            <h2 id="schedule-title" className="text-base font-semibold text-ink">Jadwal kelas</h2>
                            <p className="mt-0.5 text-xs text-steel">{schedules.length} jadwal tersedia</p>
                        </div>
                        {schedules.length ? (
                            <ul className="divide-y divide-hairline-soft">
                                {schedules.map((schedule) => (
                                    <li key={schedule.id} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-3.5 text-sm sm:px-6">
                                        <div>
                                            <p className="font-medium text-ink">{schedule.date ?? 'Tanggal belum ditentukan'}</p>
                                            <p className="mt-0.5 text-xs text-steel">{schedule.time_from ?? '—'} – {schedule.time_to ?? '—'}</p>
                                        </div>
                                        <span className={badgePending}>Kuota {schedule.quota ?? '—'}</span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="px-5 py-8 text-sm text-steel sm:px-6">Belum ada jadwal untuk kelas ini.</p>
                        )}
                    </section>}

                    <section className={`${card} overflow-hidden`} aria-labelledby="trainer-list-title">
                        <div className="border-b border-hairline px-5 py-4 sm:px-6">
                            <h2 id="trainer-list-title" className="text-base font-semibold text-ink">Pemateri</h2>
                            <p className="mt-0.5 text-xs text-steel">{trainers.length} pemateri ditetapkan</p>
                        </div>
                        {trainers.length ? (
                            <ul className="divide-y divide-hairline-soft">
                                {trainers.map((trainer) => (
                                    <li key={trainer.id} className="flex items-center gap-3 px-5 py-3 sm:px-6">
                                        <img src={trainer.photo_src} alt="" className="h-10 w-10 shrink-0 rounded-full border border-hairline object-cover" />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-ink">{trainer.name}</p>
                                            {trainer.jabatan && <p className="truncate text-xs text-steel">{trainer.jabatan}</p>}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="px-5 py-8 text-sm text-steel sm:px-6">Belum ada pemateri yang ditetapkan.</p>
                        )}
                    </section>
                </div>


            </div>
        </AdminLayout>
    );
}
